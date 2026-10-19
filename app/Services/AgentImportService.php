<?php
namespace App\Services;

use App\Models\Activity;
use App\Models\Agent;
use App\Models\AgentException;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Importe des agents depuis un classeur Excel/CSV (feuille « AGENTS » du classeur Excel fourni, ou 1re feuille).
 * Colonnes reconnues par leur en-tête : NOM…, IM, LOCALITE…, TYPE, DISPONIBLE, CORPS, GRADE, INDICE,
 * FONCTION…, HISTORIQUE…, ORDRE…, et une colonne par CODE d'activité (valeurs F / X = exceptions).
 */
class AgentImportService
{
    public function import(string $path): array
    {
        $ss = IOFactory::load($path);
        $sh = $ss->getSheetByName('AGENTS') ?? $ss->getSheet(0);
        $rows = $sh->toArray(null, true, true, false);
        $h = null;
        foreach ($rows as $i => $r) {
            foreach ($r as $c) if (is_string($c) && str_starts_with(Str::upper(trim($c)), 'NOM')) { $h = $i; break 2; }
        }
        if ($h === null) throw new \RuntimeException('En-tête « NOM ET PRENOMS » introuvable dans le fichier.');

        $acts = Activity::all()->keyBy(fn($a) => Str::upper(trim($a->code)));
        $map = []; $exCols = [];
        foreach ($rows[$h] as $ci => $t) {
            $k = Str::upper(trim((string)$t));
            if ($k === '') continue;
            if (str_starts_with($k, 'NOM')) $map['name'] = $ci;
            elseif ($k === 'IM') $map['im'] = $ci;
            elseif (str_starts_with($k, 'LOCALIT')) $map['locality'] = $ci;
            elseif ($k === 'TYPE') $map['type'] = $ci;
            elseif (str_starts_with($k, 'DISPON')) $map['available'] = $ci;
            elseif ($k === 'CORPS') $map['corps'] = $ci;
            elseif ($k === 'GRADE') $map['grade'] = $ci;
            elseif ($k === 'INDICE') $map['indice'] = $ci;
            elseif (str_starts_with($k, 'FONCTION')) $map['function'] = $ci;
            elseif (str_starts_with($k, 'HISTORIQ')) $map['history_amount'] = $ci;
            elseif (str_starts_with($k, 'ORDRE')) $map['draw_order'] = $ci;
            elseif (str_starts_with($k, 'MOTIF')) $map['notes'] = $ci;
            elseif ($acts->has($k)) $exCols[$ci] = $acts[$k];
        }
        $created = $updated = $exc = 0;
        foreach (array_slice($rows, $h + 1) as $r) {
            $name = trim((string)($r[$map['name']] ?? ''));
            if ($name === '') continue;
            $get = fn($key) => isset($map[$key]) ? trim((string)($r[$map[$key]] ?? '')) : null;
            $im = $get('im');
            $data = ['name' => $name, 'im' => $im ?: null, 'locality' => $get('locality') ?: null];
            if (($t = $get('type')) !== null && $t !== '') $data['type'] = Str::upper($t) === 'EXTERNE' ? 'EXTERNE' : 'DIRECTION';
            if (($v = $get('available')) !== null && $v !== '') $data['available'] = Str::upper($v) !== 'NON';
            foreach (['corps', 'grade', 'indice', 'function', 'notes'] as $f) if (($v = $get($f)) !== null && $v !== '') $data[$f] = $v;
            if (($v = $get('history_amount')) !== null && is_numeric($v)) $data['history_amount'] = (int)$v;
            if (($v = $get('draw_order')) !== null && is_numeric($v)) $data['draw_order'] = (int)$v;

            $q = Agent::query();
            $agent = ($im && Str::lower($im) !== 'en cours') ? $q->where('im', $im)->first() : $q->where('name', $name)->first();
            if ($agent) { $agent->update($data); $updated++; } else { $agent = Agent::create($data); $created++; }
            foreach ($exCols as $ci => $act) {
                $m = Str::upper(trim((string)($r[$ci] ?? '')));
                if (in_array($m, ['F', 'X'])) { AgentException::updateOrCreate(['agent_id' => $agent->id, 'activity_id' => $act->id], ['mode' => $m]); $exc++; }
            }
        }
        return compact('created', 'updated', 'exc');
    }
}

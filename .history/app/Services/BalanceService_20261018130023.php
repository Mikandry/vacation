<?php
namespace App\Services;

use App\Models\Activity;
use App\Models\Agent;
use App\Models\Setting;
use Illuminate\Support\Str;

class BalanceService
{
    public static function services(array $s): array
    {
        $out = [];
        foreach (preg_split('/\R/', trim($s['services'])) as $line) {
            if (trim($line) === '') continue;
            [$code, $name] = array_pad(explode('|', $line, 2), 2, '');
            $out[trim($code)] = trim($name);
        }
        return $out;
    }

    public function compute(): array
    {
        $s = Setting::all_();
        $hist = $s['use_history'] === 'OUI';
        $tol = (float)$s['tolerance'] / 100;
        $acts = Activity::where('active', true)->orderBy('position')->orderBy('id')->get();
        $actMap = $acts->keyBy('id');
        $agents = Agent::with('assignments')->get()->sortBy(fn($a) => Str::upper(Str::ascii($a->name)))->values();

        $rows = [];
        foreach ($agents as $a) {
            $mine = $a->assignments->filter(fn($x) => $actMap->has($x->activity_id));
            $amount = $mine->sum(fn($x) => $actMap[$x->activity_id]->amount);
            $rows[] = [
                'agent' => $a,
                'ids' => $mine->pluck('activity_id')->all(),
                'acts' => $mine->map(fn($x) => $actMap[$x->activity_id])->values(),
                'count' => $mine->count(),
                'amount' => $amount,
                'total' => $amount + ($hist ? (int)$a->history_amount : 0),
                'base' => $a->type === 'DIRECTION' && $a->available,
            ];
        }
        $base = collect($rows)->where('base', true);
        $n = $base->count();
        $mean = $n ? $base->avg('total') : 0;
        $min = $n ? $base->min('total') : 0;
        $max = $n ? $base->max('total') : 0;
        $std = $n ? sqrt($base->avg(fn($r) => ($r['total'] - $mean) ** 2)) : 0;

        foreach ($rows as &$r) {
            if (!$r['agent']->available) { $r['status'] = 'Indisponible'; $r['dev'] = null; }
            elseif ($r['agent']->type !== 'DIRECTION') { $r['status'] = 'Externe'; $r['dev'] = null; }
            elseif ($mean == 0) { $r['status'] = '-'; $r['dev'] = 0; }
            else {
                $r['dev'] = $r['total'] / $mean - 1;
                $r['status'] = abs($r['dev']) <= $tol ? 'Équilibré' : ($r['dev'] > 0 ? 'Sur-chargé' : 'Sous-chargé');
            }
        }
        unset($r);
        $inTol = collect($rows)->where('status', 'Équilibré')->count();
        $pct = $n ? $inTol / $n * 100 : 0;
        $verdict = $n === 0 ? '—' : ($pct >= $s['threshold_ok'] ? 'ÉQUILIBRÉ' : ($pct >= $s['threshold_acc'] ? 'ACCEPTABLE' : 'DÉSÉQUILIBRÉ'));

        // par activité
       $perAct[] = ['activity' => $act, 'quota' => $act->quota, 'dir' => $dir, 'ext' => $tot - $dir, 'total' => $tot,
    'decree' => $act->decree_total, 'gap_decree' => $tot - $act->decree_total, 'money' => $tot * $act->amount, 'check' => $check];
        foreach ($acts as $act) {
            $assigned = collect($rows)->filter(fn($r) => in_array($act->id, $r['ids']));
            $dir = $assigned->where('base', true)->count();
            $tot = $assigned->count();
            $check = $act->manual ? '✎ Choix manuel'
    : ($act->quota == 0 ? ($tot > $dir ? 'Quota à définir (forcés seuls)' : '⚠ Quota à définir')
    : ($dir > $act->quota ? '⚠ Dépasse le quota (forçages)' : ($dir < $act->quota ? '⚠ Quota non atteint' : '✔ OK')));
            $perAct[] = ['id' => $act->id, 'code' => $act->code, 'name' => $act->name, 'quota' => $act->quota, 'dir' => $dir, 'total' => $tot, 'check' => $check];  
        }
        // par service
        $perSvc = [];
        foreach (self::services($s) as $code => $name) {
            $g = $base->filter(fn($r) => $r['agent']->locality === $code);
            $avg = $g->count() ? $g->avg('total') : 0;
            $dev = $mean ? $avg / $mean - 1 : 0;
            $perSvc[] = ['code' => $code, 'name' => $name, 'n' => $g->count(), 'assign' => $g->sum('count'), 'sum' => $g->sum('total'), 'avg' => $avg, 'dev' => $dev,
                'status' => $g->count() === 0 ? '—' : (abs($dev) <= $tol / 2 ? '✔ Équilibré' : ($dev > 0 ? '⚠ Plus chargé' : '⚠ Moins chargé'))];
        }
        // distributions
        $step = max(1, (int)$s['band_step']);
        $bands = [];
        for ($k = 0; $k < 10; $k++) {
            $lo = $k * $step; $hi = ($k + 1) * $step;
            $c = $base->filter(fn($r) => $r['total'] >= $lo && ($k == 9 || $r['total'] < $hi))->count();
            $bands[] = ['label' => $k < 9 ? ($lo / 1000) . '–' . ($hi / 1000) : '≥ ' . ($lo / 1000), 'count' => $c];
        }
        $byCount = [];
        for ($k = 0; $k <= 6; $k++) $byCount[] = ['label' => $k < 6 ? (string)$k : '6 et +', 'count' => $base->filter(fn($r) => $k < 6 ? $r['count'] == $k : $r['count'] >= 6)->count()];

        // contrôles
        $ims = $agents->pluck('im')->filter(fn($v) => $v && Str::lower($v) !== 'en cours')->countBy();
        $checks = [
            ['Activités sans quota défini', collect($perAct)->filter(fn($x) => $x['quota'] == 0 && !$x['activity']->manual)->count(), 'warn'],
            ['Activités où le quota n’est pas atteint (manque d’agents éligibles)', collect($perAct)->filter(fn($x) => str_contains($x['check'], 'non atteint'))->count(), 'warn'],
            ['Activités où les forçages dépassent le quota', collect($perAct)->filter(fn($x) => str_contains($x['check'], 'Dépasse'))->count(), 'warn'],
            ['IM en doublon', $ims->filter(fn($c) => $c > 1)->count(), 'warn'],
            ['Agents sans IM (ou « en cours »)', $agents->filter(fn($a) => !$a->im || Str::lower($a->im) === 'en cours')->count(), 'info'],
            ['Agents indisponibles', $agents->where('available', false)->count(), 'info'],
            ['Agents de la direction sans aucune activité', $base->where('count', 0)->count(), 'warn'],
        ];

        return [
            'settings' => $s, 'rows' => $rows, 'acts' => $acts, 'perAct' => $perAct, 'perSvc' => $perSvc, 'bands' => $bands, 'byCount' => $byCount, 'checks' => $checks,
            'verdict' => $verdict,
            'stats' => ['n' => $n, 'assignments' => $base->sum('count'), 'sum' => $base->sum('amount'), 'mean' => $mean, 'min' => $min, 'max' => $max,
                'range' => $max - $min, 'cv' => $mean ? $std / $mean : 0, 'pct' => $pct,
                'over' => collect($rows)->where('status', 'Sur-chargé')->count(), 'under' => collect($rows)->where('status', 'Sous-chargé')->count(),
                'none' => $base->where('count', 0)->count()],
        ];
    }
}

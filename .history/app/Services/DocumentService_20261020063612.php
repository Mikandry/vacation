<?php
namespace App\Services;

use App\Models\Activity;
use App\Models\Agent;
use App\Models\Setting;
use App\Models\Visa;
use App\Support\Words;
use Carbon\Carbon;
use Illuminate\Support\Str;

/** Prépare les données communes aux documents (décision, note, présence, états). */
class DocumentService
{
    public const FIRST_PAGE = 30;   // lignes sur la page 2 de la décision (après les visas)
    public const NEXT_PAGES = 34;
    private const MOIS = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

    /**
     * $opt : 'irsa' (taux % imposé pour ce téléchargement), 'dates' (true/false : afficher les dates sur la présence).
     */
    public function data(Activity $activity, array $opt = []): array
    {
        $s = Setting::all_();
        $agents = Agent::whereHas('assignments', fn($q) => $q->where('activity_id', $activity->id))->get()
            ->sortBy(fn($a) => Str::upper(Str::ascii($a->name)))->values();
        $n = $agents->count();

        // ---- décision : pages de 30 puis 34 lignes
        $chunks = []; $i = 0; $first = true;
        while ($i < $n || $first) {
            $size = $first ? self::FIRST_PAGE : self::NEXT_PAGES;
            $part = $agents->slice($i, $size)->values();
            $chunks[] = ['offset' => $i, 'rows' => $part, 'words' => Words::of($part->count())];
            $i += $size;
            $first = false;
        }

        // ---- IRSA et décompte
        $irsaRate = (isset($opt['irsa']) && is_numeric($opt['irsa']))
            ? max(0, min(100, (float) $opt['irsa']))
            : (float) str_replace(',', '.', $s['irsa_rate']);
        $gross = (int) $activity->amount;
        $irsaAmt = (int) round($gross * $irsaRate / 100);
        $net = $gross - $irsaAmt;
        $perPage = max(5, (int) ($s['decompte_rows'] ?: 25));
        $dec = [];
        $cum = ['g' => 0, 'i' => 0, 'n' => 0];
        for ($k = 0; $k === 0 || $k < $n; $k += $perPage) {
            $part = $agents->slice($k, $perPage)->values();
            $c = $part->count();
            $page = ['g' => $c * $gross, 'i' => $c * $irsaAmt, 'n' => $c * $net];
            $report = $cum;
            $cum = ['g' => $cum['g'] + $page['g'], 'i' => $cum['i'] + $page['i'], 'n' => $cum['n'] + $page['n']];
            $dec[] = ['offset' => $k, 'rows' => $part, 'page' => $page, 'report' => $report, 'cum' => $cum, 'first' => $k === 0];
        }

        // ---- présence : feuilles de 15 colonnes maximum
        $showDates = (array_key_exists('dates', $opt) && $opt['dates'] !== null)
            ? (bool) $opt['dates']
            : (($s['presence_show_dates'] ?? 'OUI') === 'OUI');
        $maxCols = max(1, min(31, (int) ($s['presence_max_cols'] ?: 15)));
        $days = max(1, (int) $activity->days);
        $label = fn(int $idx) => ($showDates && $activity->start_date) ? $activity->start_date->copy()->addDays($idx)->format('d/m/Y') : '';
        $series = [];
        if ($showDates && $days > $maxCols) {
            for ($from = 0; $from < $days; $from += $maxCols) {
                $cols = min($maxCols, $days - $from);
                $labels = [];
                for ($j = 0; $j < $cols; $j++) $labels[] = $label($from + $j);
                $series[] = ['cols' => $cols, 'labels' => $labels, 'from' => $from + 1, 'to' => $from + $cols];
            }
        } else {
            $cols = min($days, $maxCols);
            $labels = [];
            for ($j = 0; $j < $cols; $j++) $labels[] = $label($j);
            $series[] = ['cols' => $cols, 'labels' => $labels, 'from' => 1, 'to' => $cols];
        }

        $totalMoney = $n * $gross;
        return [
            'a' => $activity, 's' => $s, 'agents' => $agents, 'n' => $n,
            'nWords' => mb_strtoupper(Words::of($n)),
            'visas' => Visa::where('included', true)->orderBy('position')->orderBy('id')->get(),
            'chunks' => $chunks,
            'series' => $series, 'showDates' => $showDates,
            'amount' => $gross, 'totalMoney' => $totalMoney,
            'totalWords' => mb_strtoupper(Words::of($totalMoney)),
            'irsaRate' => $irsaRate, 'irsaAmt' => $irsaAmt, 'net' => $net,
            'dec' => $dec,
            'totalGross' => $cum['g'], 'totalIrsa' => $cum['i'], 'totalNet' => $cum['n'],
            'totalNetWords' => mb_strtoupper(Words::of($cum['n'])),
            'file' => Str::slug($activity->code . '-' . $s['session']),
        ];
    }

    /** IM écrit avec espaces : 370587 → « 370 587 ». */
    public function fmtIm($im): string
    {
        $im = trim((string) $im);
        $digits = preg_replace('/\s+/', '', $im);
        return ($digits !== '' && ctype_digit($digits)) ? strrev(trim(chunk_split(strrev($digits), 3, ' '))) : $im;
    }

    public function rate(float $r): string
    {
        return rtrim(rtrim(number_format($r, 1, ',', ''), '0'), ',');
    }

    private function jour(Carbon $c): string { return $c->day === 1 ? '1er' : (string) $c->day; }

    /** « du 12 au 26 octobre 2026 » à partir de la date de début et du nombre de jours. */
    public function periode(Activity $a): string
    {
        if (!$a->start_date) return 'du ……… au ………';
        $d1 = $a->start_date->copy();
        $d2 = $a->start_date->copy()->addDays(max(0, $a->days - 1));
        $m1 = self::MOIS[$d1->month - 1];
        $m2 = self::MOIS[$d2->month - 1];
        if ($d1->isSameDay($d2)) return 'le ' . $this->jour($d1) . " $m1 {$d1->year}";
        if ($d1->year !== $d2->year) return 'du ' . $this->jour($d1) . " $m1 {$d1->year} au " . $this->jour($d2) . " $m2 {$d2->year}";
        if ($d1->month !== $d2->month) return 'du ' . $this->jour($d1) . " $m1 au " . $this->jour($d2) . " $m2 {$d2->year}";
        return 'du ' . $this->jour($d1) . ' au ' . $this->jour($d2) . " $m2 {$d2->year}";
    }

    /** Remplace les jetons {activite}, {session}, {jours}… dans un modèle de texte. */
    public function tpl(string $tpl, array $d): string
    {
        $a = $d['a'];
        $s = $d['s'];
        return strtr($tpl, [
            '{activite}' => (string) $a->name,
            '{code}' => (string) $a->code,
            '{titre}' => (string) $a->title,
            '{TITRE}' => mb_strtoupper((string) $a->title),
            '{session}' => (string) $s['session'],
            '{jours}' => (string) $a->days,
            '{agents}' => (string) $d['n'],
            '{agents_lettres}' => (string) $d['nWords'],
            '{direction}' => (string) $s['direction'],
            '{lieu}' => (string) $s['place'],
            '{date}' => $s['decision_date'] !== '' ? (string) $s['decision_date'] : '……………………',
            '{ministere}' => (string) $s['ministry'],
            '{ref_arrete}' => (string) $s['ref_arrete'],
            '{numero}' => (string) ($a->note_number ?: ($s['note_number'] ?? '')),
            '{taux}' => number_format($a->rate, 0, ',', ' '),
            '{montant}' => number_format($d['amount'], 0, ',', ' '),
            '{total}' => number_format($d['totalMoney'], 0, ',', ' '),
            '{total_lettres}' => (string) $d['totalWords'],
            '{irsa}' => $this->rate((float) ($d['irsaRate'] ?? 0)),
            '{net_lettres}' => (string) ($d['totalNetWords'] ?? ''),
            '{periode}' => $this->periode($a),
            '{debut}' => $a->start_date ? $a->start_date->format('d/m/Y') : '………',
            '{fin}' => $a->start_date ? $a->start_date->copy()->addDays(max(0, $a->days - 1))->format('d/m/Y') : '………',
        ]);
    }

    public function decisionTitle(array $d): string
    {
        return 'DECISION N° ' . $d['s']['decision_number'] . ' portant désignation des Agents Fonctionnaires pour les travaux de '
            . $d['a']->title . '. SESSION ' . $d['s']['session'];
    }
    public function article1(array $d): string
    {
        return 'Article Premier : Les fonctionnaires dont les noms suivants sont désignés membres des commissions chargées des travaux de '
            . $d['a']->title . ', session ' . $d['s']['session'] . ' :';
    }
    public function article2(array $d): string
    {
        return 'Article 2 : Les Fonctionnaires désignés à l’article premier ci-dessus ont droit à l’indemnité de vacations prévue par ' . $d['s']['ref_arrete'] . '.';
    }
    public function signDate(array $d): string
    {
        return 'Fait à ' . $d['s']['place'] . ', le ' . ($d['s']['decision_date'] ?: '……………………');
    }
    public function pageTail(array $chunk): string
    {
        $c = $chunk['rows']->count();
        return $c ? 'Arrêtée la présente page au nombre de ' . $chunk['words'] . ' agent' . ($c > 1 ? 's' : '') . ' (' . $c . ').' : '';
    }
}
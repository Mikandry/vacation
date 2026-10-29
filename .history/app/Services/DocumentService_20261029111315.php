<?php
namespace App\Services;

use App\Models\Activity;
use App\Models\Agent;
use App\Models\AgentSubgroup;
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
        $all = Agent::whereHas('assignments', fn($q) => $q->where('activity_id', $activity->id))->get();
        $byName = fn($a) => Str::upper(Str::ascii($a->name));

        if (!$activity->is_quarantine) {
            $d = $this->build($activity, $all->sortBy($byName)->values(), $opt, null);
            $d['quarantine'] = false;
            $d['groups'] = [];
            $d['dpages'] = $this->decisionPages([['label' => null, 'rows' => $d['agents'], 'g' => null]], $d['s'], false);
            $d['parts'] = [$d];
            return $d;
        }

        // ---- quarantaine : OG, Superviseur, Coordonateur, Sous pli (+ « Non réparti » s'il reste des agents sans sous-groupe)
        $map = AgentSubgroup::where('activity_id', $activity->id)->pluck('subgroup', 'agent_id');
        $labels = Activity::SUBGROUPS + ['NR' => 'Non réparti'];
        $buckets = array_fill_keys(array_keys($labels), []);
        foreach ($all as $ag) {
            $k = $map[$ag->id] ?? 'NR';
            $buckets[isset($labels[$k]) ? $k : 'NR'][] = $ag;
        }
        $ordered = collect();
        $groups = [];
        $parts = [];
        $offset = 0;
        foreach ($buckets as $key => $list) {
            if (!count($list)) continue;
            $rows = collect($list)->sortBy($byName)->values();
            $ordered = $ordered->concat($rows);
            $groups[] = [
                'key' => $key, 'label' => $labels[$key], 'rows' => $rows, 'n' => $rows->count(),
                'nWords' => mb_strtoupper(Words::of($rows->count())), 'offset' => $offset,
                'chunks' => $this->chunk($rows, $offset, count($groups) === 0 ? self::FIRST_PAGE : self::NEXT_PAGES),
            ];
            $offset += $rows->count();
            $parts[] = $this->build($activity, $rows, $opt, ['key' => $key, 'label' => $labels[$key]]);
        }
        $d = $this->build($activity, $ordered->values(), $opt, null);
        $d['quarantine'] = true;
        $d['groups'] = $groups;
        $d['parts'] = $parts ?: [$d];
        return $d;
    }


    /** Hauteur estimée (points) d'une ligne du décompte : une ligne de plus quand le nom ou la localité passe à la ligne. */
    private function decRowH($ag): float
    {
        $lines = max(1, (int) ceil(mb_strlen((string) $ag->name) / 26), (int) ceil(mb_strlen((string) $ag->locality) / 14));
        return 18 + ($lines - 1) * 10;
    }

    /**
     * Découpe les agents en pages du décompte : chaque page tient réellement sur UNE feuille A4,
     * avec son propre « total de la page » et « total à reporter » en bas.
     * La première page est plus courte (logo + en-tête). Retourne une liste de [offset, nombre].
     * Réglage fin : Paramètres > « Marge de sécurité » (augmentez-la si une page déborde encore).
     */
    private function packDecompte($agents, array $s, int $perPage): array
    {
        $safety = max(0, (int) ($s['decompte_safety'] ?? 25));
        $pageH = 842 - 33 - $safety;                       // A4 portrait moins les marges de @page
        $logoH = (($s['show_logo'] ?? 'OUI') === 'OUI') ? 52 : 0;
        $hdrLines = max(count(preg_split('/\R/', (string) $s['doc_header'])), 8);
        $headH = $logoH + $hdrLines * 9.5 + 16;            // logo + bloc ministère de la 1re page
        $theadH = 28;                                      // ligne de titres du tableau
        $capFirst = max(60, $pageH - $headH - $theadH - 36);   // 36 = total de la page + total à reporter
        $capNext = max(60, $pageH - $theadH - 54);             // 54 = report + total de la page + total à reporter
        $footer = 90;                                      // phrase de fin + date + signature (dernière page)

        $pages = []; $start = 0; $used = 0; $cnt = 0; $cap = $capFirst;
        foreach ($agents->values() as $i => $ag) {
            $h = $this->decRowH($ag);
            if ($cnt > 0 && ($used + $h > $cap || $cnt >= $perPage)) {
                $pages[] = [$start, $cnt];
                $start = $i; $used = 0; $cnt = 0; $cap = $capNext;
            }
            $used += $h; $cnt++;
        }
        if ($cnt > 0) {
            if ($cnt > 1 && $used + $footer > $cap) {      // le pied de page ne tient pas : au moins un agent reste avec lui
                $pages[] = [$start, $cnt - 1];
                $pages[] = [$start + $cnt - 1, 1];
            } else {
                $pages[] = [$start, $cnt];
            }
        }
        return $pages ?: [[0, 0]];
    }

    /** Découpe une liste d'agents en pages de la décision (première page : $firstSize lignes, puis 34). */
    private function chunk($agents, int $offset0, int $firstSize): array
    {
        $chunks = []; $i = 0; $size = $firstSize; $n = $agents->count();
        while ($i < $n || $i === 0) {
            $part = $agents->slice($i, $size)->values();
            $chunks[] = ['offset' => $offset0 + $i, 'local' => $i, 'rows' => $part, 'words' => Words::of($part->count())];
            $i += $size;
            $size = self::NEXT_PAGES;
        }
        return $chunks;
    }

    /** Données d'un document pour une liste d'agents (toute l'activité, ou un seul sous-groupe). */
    private function build(Activity $activity, $agents, array $opt, ?array $sub): array
    {
        $s = Setting::all_();
        $n = $agents->count();

        // ---- décision : pages de 30 puis 34 lignes
        $chunks = $this->chunk($agents, 0, self::FIRST_PAGE);

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
        foreach ($this->packDecompte($agents, $s, $perPage) as [$k, $c]) {
            $part = $agents->slice($k, $c)->values();
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
            'sub' => $sub['label'] ?? null, 'subKey' => $sub['key'] ?? null,
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
        $sub = $d['sub'] ?? null;
        $suf = $sub ? ' – ' . $sub : '';   // « Quarantaine – Superviseur »
        return strtr($tpl, [
            '{sousgroupe}' => (string) $sub,
            '{activite}' => (string) $a->name . $suf,
            '{code}' => (string) $a->code . $suf,
            '{titre}' => (string) $a->title . $suf,
            '{TITRE}' => mb_strtoupper((string) $a->title . $suf),
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

    /** N° de la décision : Laravel retire les espaces de début de champ ; on remet un blanc visible devant « -2026/MEN ». */
    public function decisionNumber(array $d): string
    {
        $n = trim((string) $d['s']['decision_number']);
        $nbsp = "\u{00A0}";
        if ($n === '' || !ctype_digit(mb_substr($n, 0, 1))) $n = str_repeat($nbsp, 10) . $n;   // numéro à compléter à la main
        return str_replace(' ', $nbsp, $n);
    }
    public function decisionTitle(array $d): string
    {
        return 'DECISION N° ' . $this->decisionNumber($d) . ' portant désignation des Agents Fonctionnaires pour les travaux de '
            . $d['a']->title . '.';
    }
    /** « SESSION 2026 » : ligne centrée sous le titre de la décision. */
    public function decisionSession(array $d): string
    {
        return 'SESSION ' . $d['s']['session'];
    }
    public function article1(array $d): string
    {
        return 'Article Premier : Les fonctionnaires dont les noms suivants sont désignés membres des commissions chargées des travaux de '
            . $d['a']->title . ', session ' . $d['s']['session'] . ' :';
    }
    public function article2(array $d): string
    {
        return 'Les Fonctionnaires désignés à l’article premier ci-dessus ont droit à l’indemnité de vacations prévue par ' . $d['s']['ref_arrete'] . '.';
    }

    /**
     * Articles qui suivent l'article premier, numérotés automatiquement (2, 3, 4…) :
     *  - l'article « indemnité de vacations » + ligne budgétaire (désactivable : Paramètres > Articles de la décision) ;
     *  - les articles supplémentaires (un par ligne dans les paramètres) ;
     *  - l'article final (enregistrement / communication).
     * Retourne [['label' => 'Article 2', 'text' => '…', 'budget' => bool], …].
     */
    public function articles(array $d): array
    {
        $s = $d['s'];
        $items = [];
        if (($s['decision_art_indemnite'] ?? 'OUI') === 'OUI') $items[] = ['text' => $this->article2($d), 'budget' => true];
        foreach (preg_split('/\R/', (string) ($s['decision_articles_extra'] ?? '')) as $line) {
            $line = trim($line);
            if ($line !== '') $items[] = ['text' => $this->tpl($line, $d), 'budget' => false];
        }
        $final = trim((string) ($s['decision_art_final'] ?? 'La présente décision sera enregistrée et communiquée partout où besoin sera.'));
        if ($final !== '') $items[] = ['text' => $this->tpl($final, $d), 'budget' => false];
        foreach ($items as $i => &$it) $it['label'] = 'Article ' . ($i + 2);
        unset($it);
        return $items;
    }

    /** Phrase de fin de l'état de vacations : la somme en lettres est le NET (IRSA déduit). */
    public function etatFooter(array $d): string
    {
        $d['totalWords'] = $d['totalNetWords'];
        $d['totalMoney'] = $d['totalNet'];
        return $this->tpl($d['s']['etat_footer'], $d);
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

    /** « Arrêté le sous-groupe Superviseur au nombre de QUATRE (4) agents. » */
    public function groupTail(array $g): string
    {
        return 'Arrêté le sous-groupe « ' . $g['label'] . ' » au nombre de ' . $g['nWords'] . ' (' . $g['n'] . ') agent' . ($g['n'] > 1 ? 's' : '') . '.';
    }
}

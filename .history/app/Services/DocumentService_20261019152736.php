<?php
namespace App\Services;

use App\Models\Activity;
use App\Models\Agent;
use App\Models\Setting;
use App\Models\Visa;
use App\Support\Words;
use Illuminate\Support\Str;

/** Prépare les données communes aux documents (décision, présence, état de vacations). */
class DocumentService
{
    public const FIRST_PAGE = 30;   // lignes sur la page 2 (après les visas)
    public const NEXT_PAGES = 34;   // lignes par page suivante (laisse la place au bloc final)
     private const MOIS = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
      private function jour(Carbon $c): string { return $c->day === 1 ? '1er' : (string) $c->day; }
    public function data(Activity $activity): array
    {
        $s = Setting::all_();
        $agents = Agent::whereHas('assignments', fn($q) => $q->where('activity_id', $activity->id))->get()
            ->sortBy(fn($a) => Str::upper(Str::ascii($a->name)))->values();
        $n = $agents->count();

        $chunks = []; $i = 0; $first = true;
        while ($i < $n || $first) {
            $size = $first ? self::FIRST_PAGE : self::NEXT_PAGES;
            $part = $agents->slice($i, $size)->values();
            $chunks[] = ['offset' => $i, 'rows' => $part, 'words' => Words::of($part->count())];
            $i += $size; $first = false;
        }
        $dates = null;
        if ($activity->start_date) {
            $dates = [];
            for ($d = 0; $d < $activity->days; $d++) $dates[] = $activity->start_date->copy()->addDays($d)->format('d/m');
        }
        return [
            'a' => $activity, 's' => $s, 'agents' => $agents, 'n' => $n,
            'nWords' => mb_strtoupper(Words::of($n)),
            'visas' => Visa::where('included', true)->orderBy('position')->orderBy('id')->get(),
            'chunks' => $chunks, 'dates' => $dates,
            'amount' => $activity->amount, 'totalMoney' => $n * $activity->amount,
            'totalWords' => mb_strtoupper(Words::of($n * $activity->amount)),
            'file' => Str::slug($activity->code . '-' . $s['session']),
        ];
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
            '{session}' => (string) $s['session'],
            '{jours}' => (string) $a->days,
            '{agents}' => (string) $d['n'],
            '{direction}' => (string) $s['direction'],
            '{lieu}' => (string) $s['place'],
            '{date}' => (string) $s['decision_date'],
            '{ministere}' => (string) $s['ministry'],
            '{ref_arrete}' => (string) $s['ref_arrete'],
            '{taux}' => number_format($a->rate, 0, ',', ' '),
            '{montant}' => number_format($d['amount'], 0, ',', ' '),
            '{total}' => number_format($d['totalMoney'], 0, ',', ' '),
            '{total_lettres}' => (string) $d['totalWords'],
            '{debut}' => $a->start_date ? $a->start_date->format('d/m/Y') : '',
            '{fin}' => $a->start_date ? $a->start_date->copy()->addDays(max(0, $a->days - 1))->format('d/m/Y') : '',
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

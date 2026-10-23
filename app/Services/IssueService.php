<?php
namespace App\Services;

use App\Models\Agent;
use App\Models\AgentException;
use Illuminate\Support\Str;

/** Détail de chaque problème listé dans le tableau « Contrôles » du tableau de bord. */
class IssueService
{
    private function act(string $label, string $url, string $class = 'btn-outline-primary'): array
    {
        return ['label' => $label, 'url' => $url, 'class' => $class];
    }

    public function get(string $key): ?array
    {
        $b = app(BalanceService::class)->compute();
        return match ($key) {
            'sans_quota' => $this->sansQuota($b),
            'quota_non_atteint' => $this->quotaNonAtteint($b),
            'depasse_quota' => $this->depasseQuota($b),
            'im_doublon' => $this->imDoublon(),
            'sans_im' => $this->sansIm(),
            'indisponibles' => $this->indisponibles(),
            'sans_activite' => $this->sansActivite($b),
            'ecart_decret' => $this->ecartDecret($b),
            default => null,
        };
    }

    private function sansQuota(array $b): array
    {
        $rows = [];
        foreach ($b['perAct'] as $x) {
            $a = $x['activity'];
            if ($a->quota == 0 && !$a->manual) {
                $rows[] = ['cells' => [$a->code, $a->name, $x['total'] . ' agent(s) forcé(s)'],
                    'actions' => [$this->act('Définir le quota', route('activities.edit', $a), 'btn-primary'),
                                  $this->act('Choisir les agents à la main', route('choix.show', $a), 'btn-outline-success')]];
            }
        }
        return ['title' => 'Activités sans quota défini',
            'help' => 'Sans quota, la répartition automatique ne désigne aucun agent de la direction pour ces activités. Définissez le nombre d’agents, ou choisissez-les à la main.',
            'headers' => ['Code', 'Activité', 'Agents actuellement'], 'rows' => $rows];
    }

    private function quotaNonAtteint(array $b): array
    {
        $avail = Agent::where('type', 'DIRECTION')->where('available', true)->count();
        $rows = [];
        foreach ($b['perAct'] as $x) {
            $a = $x['activity'];
            if (!$a->manual && $a->quota > 0 && $x['dir'] < $a->quota) {
                $excl = AgentException::where('activity_id', $a->id)->where('mode', 'X')->count();
                $rows[] = ['cells' => [$a->code, $a->quota, $x['dir'], $a->quota - $x['dir'], "$avail disponibles · $excl exclus (X)"],
                    'actions' => [$this->act('Choisir les agents', route('choix.show', $a), 'btn-primary'),
                                  $this->act('Réduire le quota', route('activities.edit', $a)),
                                  $this->act('Paramètres', route('settings.index'), 'btn-outline-secondary')]];
            }
        }
        return ['title' => 'Quota non atteint',
            'help' => 'Il manque des agents. Causes possibles : trop peu d’agents disponibles, agents exclus (X), chevauchements de dates évités par la répartition (désactivable dans Paramètres), agents indisponibles. Choisissez des agents à la main ou réduisez le quota.',
            'headers' => ['Code', 'Quota', 'Affectés (direction)', 'Manque', 'Situation'], 'rows' => $rows];
    }

    private function depasseQuota(array $b): array
    {
        $rows = [];
        foreach ($b['perAct'] as $x) {
            $a = $x['activity'];
            if (!$a->manual && $a->quota > 0 && $x['dir'] > $a->quota) {
                $forced = AgentException::where('activity_id', $a->id)->where('mode', 'F')
                    ->whereHas('agent', fn($q) => $q->where('type', 'DIRECTION'))->count();
                $rows[] = ['cells' => [$a->code, $a->quota, $x['dir'], $forced . ' forcé(s) (F)'],
                    'actions' => [$this->act('Retirer des agents forcés', route('choix.show', $a), 'btn-primary'),
                                  $this->act('Augmenter le quota', route('activities.edit', $a))]];
            }
        }
        return ['title' => 'Forçages au-dessus du quota',
            'help' => 'Vous avez forcé (F) plus d’agents de la direction que le quota. Décochez des agents dans « Choisir les agents », ou augmentez le quota.',
            'headers' => ['Code', 'Quota', 'Affectés (direction)', 'Forçages'], 'rows' => $rows];
    }

    private function imDoublon(): array
    {
        $dups = Agent::whereNotNull('im')->where('im', '!=', '')->get()
            ->filter(fn($a) => Str::lower($a->im) !== 'en cours')->groupBy('im')->filter(fn($g) => $g->count() > 1);
        $rows = [];
        foreach ($dups as $im => $g) {
            foreach ($g as $a) {
                $rows[] = ['cells' => [$im, $a->name, $a->locality, $a->type],
                    'actions' => [$this->act('Corriger la fiche', route('agents.edit', $a), 'btn-primary')]];
            }
        }
        return ['title' => 'IM en doublon',
            'help' => 'Le même IM apparaît sur plusieurs fiches : soit une erreur de saisie (corrigez l’IM), soit un agent enregistré deux fois (supprimez la fiche en trop depuis la liste des agents).',
            'headers' => ['IM', 'Nom et prénoms', 'Localité', 'Type'], 'rows' => $rows];
    }

    private function sansIm(): array
    {
        $rows = [];
        foreach (Agent::orderBy('name')->get() as $a) {
            if (!$a->im || Str::lower($a->im) === 'en cours') {
                $rows[] = ['cells' => [$a->name, $a->im ?: '—', $a->locality, $a->type],
                    'actions' => [$this->act('Renseigner l’IM', route('agents.edit', $a), 'btn-primary')]];
            }
        }
        return ['title' => 'Agents sans IM',
            'help' => 'L’IM est imprimé dans la décision, la présence et les états. Renseignez-le dès que vous l’avez.',
            'headers' => ['Nom et prénoms', 'IM', 'Localité', 'Type'], 'rows' => $rows];
    }

    private function indisponibles(): array
    {
        $rows = [];
        foreach (Agent::where('available', false)->orderBy('name')->get() as $a) {
            $rows[] = ['cells' => [$a->name, $a->im, $a->locality, $a->type, $a->notes],
                'actions' => [$this->act('Rendre disponible / modifier', route('agents.edit', $a), 'btn-primary')]];
        }
        return ['title' => 'Agents indisponibles',
            'help' => 'Ces agents sont exclus de toutes les activités. Si l’indisponibilité est terminée, ouvrez la fiche et cochez « Disponible ».',
            'headers' => ['Nom et prénoms', 'IM', 'Localité', 'Type', 'Remarques'], 'rows' => $rows];
    }

    private function sansActivite(array $b): array
    {
        $rows = [];
        foreach ($b['rows'] as $r) {
            if ($r['base'] && $r['count'] === 0) {
                $a = $r['agent'];
                $rows[] = ['cells' => [$a->name, $a->im, $a->locality, number_format($a->history_amount, 0, ',', ' ')],
                    'actions' => [$this->act('Affecter à une activité', route('choix.matrix'), 'btn-primary'),
                                  $this->act('Fiche', route('agents.edit', $a))]];
            }
        }
        return ['title' => 'Agents de la direction sans aucune activité',
            'help' => 'Ces agents n’ont reçu aucune activité : la charge n’est pas équitable. Affectez-les dans « Choix manuel » (cochez une activité), ou augmentez les quotas.',
            'headers' => ['Nom et prénoms', 'IM', 'Localité', 'Historique (Ar)'], 'rows' => $rows];
    }

    private function ecartDecret(array $b): array
    {
        $rows = [];
        foreach ($b['perAct'] as $x) {
            $a = $x['activity'];
            if ($x['decree'] > 0 && $x['total'] != $x['decree']) {
                $rows[] = ['cells' => [$a->code, $x['decree'], $x['total'], sprintf('%+d', $x['gap_decree'])],
                    'actions' => [$this->act('Choisir les agents', route('choix.show', $a), 'btn-primary'),
                                  $this->act('Modifier l’activité', route('activities.edit', $a))]];
            }
        }
        return ['title' => 'Total différent du décret',
            'help' => 'Le nombre total d’agents désignés (direction + extérieurs) ne correspond pas au total prévu par le décret. Ajoutez des agents extérieurs ou ajustez le quota. Information seulement : le reste du total peut être couvert par d’autres directions.',
            'headers' => ['Code', 'Décret', 'Désignés', 'Écart'], 'rows' => $rows];
    }
}

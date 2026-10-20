<?php
namespace App\Services;

use App\Models\Activity;
use App\Models\Agent;
use App\Models\AgentException;
use App\Models\Assignment;
use App\Models\ConflictWaiver;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

/**
 * Répartition équilibrée :
 *  1. charge initiale = historique (option) + montants des activités où l'agent est FORCÉ ;
 *  2. activités traitées dans l'ordre "position" ; pour chacune :
 *     - les agents FORCÉS (F) sont toujours désignés (direction ou externes) ;
 *     - il reste (quota - forcés direction) places attribuées aux agents DIRECTION disponibles,
 *       non exclus (X), sans chevauchement de dates avec une activité déjà tenue (option),
 *       les MOINS chargés d'abord (départage : draw_order) ;
 *  3. les activités en « choix 100 % manuel » ne reçoivent que les agents forcés.
 */
class DistributionService
{
    public function run(): int
    {
        $s = Setting::all_();
        $auto = $s['auto'] === 'OUI';
        $hist = $s['use_history'] === 'OUI';
        $avoid = ($s['avoid_overlap'] ?? 'OUI') === 'OUI';

        $acts = Activity::where('active', true)->orderBy('position')->orderBy('id')->get();
        $agents = Agent::where('available', true)->get()->keyBy('id');
        $load = $agents->map(fn($a) => $hist ? (int) $a->history_amount : 0)->all();
        $waived = ConflictWaiver::all()->mapWithKeys(fn($w) => ["{$w->agent_id}-{$w->activity_a_id}-{$w->activity_b_id}" => true])->all();

        $forced = []; $excluded = []; $held = [];
        foreach (AgentException::all() as $e) {
            $act = $acts->firstWhere('id', $e->activity_id);
            if (!$act || !$agents->has($e->agent_id)) continue;
            if ($e->mode === 'F') {
                $forced[$act->id][] = $e->agent_id;
                $load[$e->agent_id] += $act->amount;
                $held[$e->agent_id][] = $act;
            } else {
                $excluded[$act->id][] = $e->agent_id;
            }
        }

        // vrai si l'agent tient déjà une autre activité qui chevauche $act (sauf conflit ignoré)
        $clash = function (int $agentId, Activity $act) use (&$held, $waived): bool {
            foreach ($held[$agentId] ?? [] as $o) {
                if ($o->id === $act->id) continue;
                if (isset($waived[ConflictService::key($agentId, $act->id, $o->id)])) continue;
                if ($act->overlap($o)) return true;
            }
            return false;
        };

        $tie = fn($a) => ($a->draw_order + $a->id / 1000) / 10000;
        $rows = [];
        foreach ($acts as $act) {
            $f = $forced[$act->id] ?? [];
            $fDir = collect($f)->filter(fn($id) => $agents[$id]->type === 'DIRECTION')->count();
            $remaining = $act->manual ? 0 : max(0, $act->quota - $fDir);
            $picked = [];
            if ($auto && $remaining > 0) {
                $picked = $agents
                    ->filter(fn($a) => $a->type === 'DIRECTION'
                        && !in_array($a->id, $f)
                        && !in_array($a->id, $excluded[$act->id] ?? [])
                        && !($avoid && $clash($a->id, $act)))
                    ->sortBy(fn($a) => $load[$a->id] + $tie($a))
                    ->take($remaining)->keys()->all();
                foreach ($picked as $id) {
                    $load[$id] += $act->amount;
                    $held[$id][] = $act;
                }
            }
            foreach ($f as $id) $rows[] = ['agent_id' => $id, 'activity_id' => $act->id, 'forced' => true];
            foreach ($picked as $id) $rows[] = ['agent_id' => $id, 'activity_id' => $act->id, 'forced' => false];
        }
        DB::transaction(function () use ($rows) {
            Assignment::query()->delete();
            foreach (array_chunk($rows, 500) as $chunk) Assignment::insert($chunk);
        });
        return count($rows);
    }

    /** Nouveau tirage de départage (agents direction). */
    public function redraw(): void
    {
        $ids = Agent::where('type', 'DIRECTION')->pluck('id')->shuffle()->values();
        foreach ($ids as $i => $id) Agent::whereKey($id)->update(['draw_order' => $i + 1]);
    }

    /** Clôture de session : ajoute les montants perçus à l'historique, puis vide les affectations. */
    public function archive(): void
    {
        $acts = Activity::all()->keyBy('id');
        DB::transaction(function () use ($acts) {
            foreach (Assignment::all()->groupBy('agent_id') as $agentId => $list) {
                $sum = $list->sum(fn($x) => $acts[$x->activity_id]->amount ?? 0);
                Agent::whereKey($agentId)->increment('history_amount', $sum);
            }
            Assignment::query()->delete();
        });
    }
}
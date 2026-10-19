<?php
namespace App\Services;

use App\Models\Activity;
use App\Models\Agent;
use App\Models\AgentException;
use App\Models\Assignment;
use Illuminate\Support\Facades\DB;

class ManualSelectionService
{
    /** Enregistre la sélection complète d'une activité puis recalcule. */
    public function sync(Activity $act, array $selectedIds, bool $manualMode): void
    {
        $selected = collect($selectedIds)->map(fn($i) => (int)$i)->unique();
        $current = Assignment::where('activity_id', $act->id)->pluck('agent_id');
        DB::transaction(function () use ($act, $selected, $current, $manualMode) {
            foreach (Agent::pluck('id') as $id) {
                $this->set($act, (int)$id, $selected->contains($id), $current->contains($id), $manualMode);
            }
            $act->update(['manual' => $manualMode]);
        });
        app(DistributionService::class)->run();
    }

    /** Coche / décoche un agent pour une activité. */
    public function set(Activity $act, int $agentId, bool $on, bool $wasAssigned, bool $manualMode): void
    {
        $key = ['agent_id' => $agentId, 'activity_id' => $act->id];
        if ($on) {
            AgentException::updateOrCreate($key, ['mode' => 'F']);
            return;
        }
        AgentException::where($key)->where('mode', 'F')->delete();
        // en mode automatique, un agent retiré alors qu'il était tiré au sort est exclu (sinon il serait re-sélectionné)
        if (!$manualMode && $wasAssigned) AgentException::updateOrCreate($key, ['mode' => 'X']);
    }
}
<?php
namespace App\Services;

use App\Models\Activity;
use App\Models\ConflictWaiver;
use App\Models\Assignment;

class ConflictService
{
    public static function key(int $agentId, int $a, int $b): string
    {
        return $agentId . '-' . min($a, $b) . '-' . max($a, $b);
    }

    /** Liste des agents présents dans deux activités qui se chevauchent (conflits non ignorés). */
    public function detect(): array
    {
        $acts = Activity::where('active', true)->whereNotNull('start_date')->get()->keyBy('id');
        $waived = ConflictWaiver::all()->mapWithKeys(fn($w) => ["{$w->agent_id}-{$w->activity_a_id}-{$w->activity_b_id}" => true])->all();
        $out = [];
        foreach (Assignment::with('agent')->get()->groupBy('agent_id') as $agentId => $list) {
            $mine = $list->filter(fn($x) => $acts->has($x->activity_id))->sortBy('activity_id')->values();
            for ($i = 0; $i < $mine->count(); $i++) {
                for ($j = $i + 1; $j < $mine->count(); $j++) {
                    $a = $acts[$mine[$i]->activity_id];
                    $b = $acts[$mine[$j]->activity_id];
                    if (isset($waived[self::key($agentId, $a->id, $b->id)])) continue;
                    $ov = $a->overlap($b);
                    if (!$ov) continue;
                    $out[] = [
                        'agent' => $mine[$i]->agent,
                        'a' => $a, 'b' => $b,
                        'forced_a' => (bool) $mine[$i]->forced, 'forced_b' => (bool) $mine[$j]->forced,
                        'from' => $ov[0], 'to' => $ov[1],
                        'days' => abs((int) $ov[0]->diffInDays($ov[1])) + 1,
                    ];
                }
            }
        }
        usort($out, fn($x, $y) => strcmp($x['agent']->name, $y['agent']->name));
        return $out;
    }

    /** Identifiants des activités dont les dates chevauchent $act. */
    public function overlappingIds(Activity $act): array
    {
        return Activity::where('active', true)->where('id', '!=', $act->id)->get()
            ->filter(fn($o) => $act->overlap($o))->pluck('id')->all();
    }
}
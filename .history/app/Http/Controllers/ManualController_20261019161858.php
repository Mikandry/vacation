<?php
namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Agent;
use App\Models\Assignment;
use App\Models\Setting;
use App\Services\DistributionService;
use App\Services\ManualSelectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ManualController extends Controller
{
    public function __construct(private ManualSelectionService $svc) {}

    private function activeActs() { return Activity::where('active', true)->orderBy('position')->orderBy('id')->get(); }
        private function localities()
    {
        return Agent::whereNotNull('locality')->where('locality', '!=', '')->distinct()->orderBy('locality')->pluck('locality');
    }

    /** Vue 1 : matrice agents × activités (agents extérieurs inclus). */
    public function matrix()
    {
        $agents = Agent::all()->sortBy([['type', 'asc'], fn($a, $b) => Str::ascii($a->name) <=> Str::ascii($b->name)])->values();
        $assigned = Assignment::all()->groupBy('agent_id')->map(fn($g) => $g->pluck('activity_id')->all());
                return view('choix.matrix', ['acts' => $this->activeActs(), 'agents' => $agents, 'assigned' => $assigned, 'localities' => $this->localities()]);
    }

    public function toggle(Request $r)
    {
        $d = $r->validate(['agent_id' => 'required|exists:agents,id', 'activity_id' => 'required|exists:activities,id', 'on' => 'required|boolean']);
        $act = Activity::findOrFail($d['activity_id']);
        $was = Assignment::where('agent_id', $d['agent_id'])->where('activity_id', $act->id)->exists();
        $this->svc->set($act, (int)$d['agent_id'], (bool)$d['on'], $was, (bool)$act->manual);
        return response()->json(['ok' => true]);
    }

    public function apply(DistributionService $d)
    {
        $n = $d->run();
        return redirect()->route('choix.matrix')->with('ok', "Répartition recalculée avec vos choix : $n affectations.");
    }

    /** Vue 2 : une activité, liste complète des agents. */
    public function show(Activity $activity)
    {
        $hist = Setting::all_()['use_history'] === 'OUI';
        $amounts = Activity::all()->keyBy('id');
        $byAgent = Assignment::all()->groupBy('agent_id');
        $agents = Agent::all()->sortBy(fn($a) => Str::upper(Str::ascii($a->name)))->values();
        $info = [];
        $overlapIds = app(\App\Services\ConflictService::class)->overlappingIds($activity);
        foreach ($agents as $a) {
            $others = ($byAgent[$a->id] ?? collect())->where('activity_id', '!=', $activity->id);
            $info[$a->id] = [
                'codes' => $others->map(fn($x) => $amounts[$x->activity_id]->code ?? '')->implode(', '),
                'total' => $others->sum(fn($x) => $amounts[$x->activity_id]->amount ?? 0) + ($hist ? (int)$a->history_amount : 0),
                
            ];
        }
        return view('choix.activity', [
            'act' => $activity, 'acts' => $this->activeActs(), 'agents' => $agents, 'info' => $info,
            'selected' => Assignment::where('activity_id', $activity->id)->pluck('agent_id')->all(),
        ]);
    }

    public function save(Request $r, Activity $activity)
    {
        $r->validate(['agents' => 'nullable|array', 'agents.*' => 'integer|exists:agents,id']);
        $this->svc->sync($activity, $r->input('agents', []), $r->boolean('manual_mode'));
        return redirect()->route('choix.show', $activity)->with('ok', 'Sélection enregistrée et répartition recalculée.');
    }

    /** Ajoute un agent extérieur et le sélectionne directement dans l'activité. */
    public function addExternal(Request $r, Activity $activity)
    {
        $d = $r->validate(['name' => 'required|string|max:255', 'im' => 'nullable|string|max:30', 'locality' => 'nullable|string|max:80']);
        $agent = Agent::create($d + ['type' => 'EXTERNE', 'available' => true]);
        $this->svc->set($activity, $agent->id, true, false, (bool)$activity->manual);
        app(DistributionService::class)->run();
        return redirect()->route('choix.show', $activity)->with('ok', "Agent extérieur « {$agent->name} » ajouté à {$activity->code}.");
    }

    public function auto(Activity $activity, DistributionService $d)
    {
        $activity->update(['manual' => false]);
        $d->run();
        return redirect()->route('choix.show', $activity)->with('ok', 'Activité repassée en automatique : vos agents cochés restent forcés, le reste est complété jusqu’au quota.');
    }
}
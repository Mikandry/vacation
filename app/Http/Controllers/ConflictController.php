<?php
namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\AgentException;
use App\Models\Assignment;
use App\Models\ConflictWaiver;
use App\Services\ConflictService;
use App\Services\DistributionService;
use App\Services\ManualSelectionService;
use Illuminate\Http\Request;

class ConflictController extends Controller
{
    public function index(ConflictService $svc)
    {
        $acts = Activity::where('active', true)->orderBy('position')->orderBy('id')->get();
        return view('conflicts.index', [
            'conflicts' => $svc->detect(),
            'acts' => $acts,
            'undated' => $acts->whereNull('start_date'),
            'waivers' => ConflictWaiver::with('agent')->get(),
            'actMap' => Activity::all()->keyBy('id'),
        ]);
    }

    /** Garde l'activité « keep » pour l'agent, le retire de « drop », puis recalcule. */
    public function resolve(Request $r, ManualSelectionService $svc, DistributionService $dist)
    {
        $d = $r->validate([
            'agent_id' => 'required|exists:agents,id',
            'keep' => 'required|exists:activities,id',
            'drop' => 'required|exists:activities,id|different:keep',
        ]);
        $agentId = (int) $d['agent_id'];
        $keep = Activity::findOrFail($d['keep']);
        $drop = Activity::findOrFail($d['drop']);

        $wasDrop = Assignment::where('agent_id', $agentId)->where('activity_id', $drop->id)->exists();
        $svc->set($drop, $agentId, false, $wasDrop, (bool) $drop->manual);   // retiré (forçage supprimé + exclusion si auto)
        $svc->set($keep, $agentId, true, true, (bool) $keep->manual);        // gardé (forcé : ne changera plus)
        $dist->run();

        return redirect()->route('conflicts.index')
            ->with('ok', "Conflit résolu : l'agent est gardé dans {$keep->code} et retiré de {$drop->code}. Répartition recalculée.");
    }

    /** Accepte volontairement que l'agent reste dans les deux activités. */
    public function waive(Request $r, DistributionService $dist)
    {
        $d = $r->validate(['agent_id' => 'required|exists:agents,id', 'a' => 'required|integer', 'b' => 'required|integer']);
        ConflictWaiver::firstOrCreate([
            'agent_id' => $d['agent_id'],
            'activity_a_id' => min($d['a'], $d['b']),
            'activity_b_id' => max($d['a'], $d['b']),
        ]);
        return redirect()->route('conflicts.index')->with('ok', 'Conflit ignoré pour cet agent (vous pourrez le rétablir plus bas).');
    }

    public function unwaive(ConflictWaiver $waiver)
    {
        $waiver->delete();
        return redirect()->route('conflicts.index')->with('ok', 'Vérification rétablie pour cet agent.');
    }
}
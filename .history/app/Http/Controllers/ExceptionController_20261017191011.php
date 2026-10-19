<?php
namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Agent;
use App\Models\AgentException;
use Illuminate\Http\Request;

/** Matrice agents × activités : F = forcer, X = exclure (enregistrement immédiat en AJAX). */
class ExceptionController extends Controller
{
    public function index(Request $r)
    {
        $agents = Agent::with('exceptions')
            ->when($r->q, fn($q, $v) => $q->where(fn($w) => $w->where('name', 'like', "%$v%")->orWhere('im', 'like', "%$v%")))
            ->when($r->type, fn($q, $v) => $q->where('type', $v))
            ->orderBy('name')->paginate(60)->withQueryString();
        return view('agents.exceptions', ['agents' => $agents, 'acts' => Activity::where('active', true)->orderBy('position')->get()]);
    }
    public function set(Request $r)
    {
        $d = $r->validate(['agent_id' => 'required|exists:agents,id', 'activity_id' => 'required|exists:activities,id', 'mode' => 'nullable|in:F,X']);
        if (empty($d['mode'])) AgentException::where('agent_id', $d['agent_id'])->where('activity_id', $d['activity_id'])->delete();
        else AgentException::updateOrCreate(['agent_id' => $d['agent_id'], 'activity_id' => $d['activity_id']], ['mode' => $d['mode']]);
        return response()->json(['ok' => true]);
    }
}

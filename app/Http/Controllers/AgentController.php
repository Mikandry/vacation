<?php
namespace App\Http\Controllers;

use App\Models\Agent;
use App\Services\AgentImportService;
use App\Services\DocumentExporter;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function index(Request $r)
    {
        $agents = Agent::query()
            ->when($r->q, fn($q, $v) => $q->where(fn($w) => $w->where('name', 'like', "%$v%")->orWhere('im', 'like', "%$v%")->orWhere('locality', 'like', "%$v%")))
            ->when($r->type, fn($q, $v) => $q->where('type', $v))
            ->when($r->locality, fn($q, $v) => $q->where('locality', $v))
            ->when($r->filled('dispo'), fn($q) => $q->where('available', $r->dispo === '1'))
            ->orderBy('name')->paginate(50)->withQueryString();

        $localities = Agent::whereNotNull('locality')->where('locality', '!=', '')
            ->distinct()->orderBy('locality')->pluck('locality');

        return view('agents.index', ['agents' => $agents, 'total' => Agent::count(), 'localities' => $localities]);
    }

    public function create()
    {
        return view('agents.form', ['agent' => new Agent(['type' => 'DIRECTION', 'available' => true, 'history_amount' => 0])]);
    }

    public function store(Request $r)
    {
        Agent::create($this->data($r));
        return redirect()->route('agents.index')->with('ok', 'Agent ajouté.');
    }

    public function edit(Agent $agent)
    {
        return view('agents.form', ['agent' => $agent]);
    }

    public function update(Request $r, Agent $agent)
    {
        $agent->update($this->data($r));
        return redirect()->route('agents.index')->with('ok', 'Agent modifié.');
    }

    public function destroy(Agent $agent)
    {
        $agent->delete();
        return back()->with('ok', 'Agent supprimé.');
    }

    private function data(Request $r): array
    {
        $d = $r->validate([
            'name' => 'required|string|max:255',
            'im' => 'nullable|string|max:30',
            'locality' => 'nullable|string|max:80',
            'type' => 'required|in:DIRECTION,EXTERNE',
            'corps' => 'nullable|string|max:120',
            'grade' => 'nullable|string|max:120',
            'indice' => 'nullable|string|max:20',
            'function' => 'nullable|string|max:150',
            'history_amount' => 'nullable|integer|min:0',
            'draw_order' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ]);
        $d['available'] = $r->boolean('available');
        $d['history_amount'] = $d['history_amount'] ?? 0;
        if (empty($d['draw_order'])) unset($d['draw_order']);
        return $d;
    }

    public function importForm() { return view('agents.import'); }

    public function import(Request $r, AgentImportService $svc)
    {
        $r->validate(['file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240']);
        try {
            $res = $svc->import($r->file('file')->getRealPath());
        } catch (\Throwable $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }
        return redirect()->route('agents.index')->with('ok', "Import terminé : {$res['created']} créé(s), {$res['updated']} mis à jour, {$res['exc']} exception(s).");
    }

    public function export(DocumentExporter $ex) { return $ex->agentsExcel(); }
}
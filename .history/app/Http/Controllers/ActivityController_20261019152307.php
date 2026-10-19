<?php
namespace App\Http\Controllers;

use App\Models\Activity;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    private function rules(): array
    {
        return ['code' => 'required|string|max:40', 'name' => 'required|string|max:255', 'title' => 'nullable|string', 'days' => 'required|integer|min:1|max:31',
            'rate' => 'required|integer|min:0', 'quota' => 'required|integer|min:0', 'decree_total' => 'nullable|integer|min:0',
            'start_date' => 'nullable|date', 'position' => 'nullable|integer|min:0', 'active' => 'nullable|boolean'],
            ;
    }
    public function index() { return view('activities.index', ['activities' => Activity::orderBy('position')->orderBy('id')->get()]); }
    public function create() { return view('activities.form', ['a' => new Activity(['days' => 10, 'rate' => 36000, 'quota' => 0, 'active' => true, 'position' => (Activity::max('position') ?? 0) + 1])]); }
    public function store(Request $r)
    {
        Activity::create($this->data($r));
        return redirect()->route('activities.index')->with('ok', 'Activité ajoutée. Relancez la répartition pour la prendre en compte.');
    }
    public function edit(Activity $activity) { return view('activities.form', ['a' => $activity]); }
    public function update(Request $r, Activity $activity)
    {
        $activity->update($this->data($r));
        return redirect()->route('activities.index')->with('ok', 'Activité modifiée. Relancez la répartition.');
    }
    public function destroy(Activity $activity)
    {
        $activity->delete();
        return redirect()->route('activities.index')->with('ok', 'Activité supprimée.');
    }
    private function data(Request $r): array
    {
        $d = $r->validate($this->rules());
        $d['active'] = $r->boolean('active');
        $d['decree_total'] = $d['decree_total'] ?? 0;
        $d['position'] = $d['position'] ?? 0;
        return $d;
    }
}

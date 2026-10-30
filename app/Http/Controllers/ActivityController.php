<?php
namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivitySubgroup;
use App\Models\AgentSubgroup;
use App\Models\Exam;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ActivityController extends Controller
{
    private function rules(): array
    {
        return [
            'code' => 'required|string|max:40',
            'name' => 'required|string|max:255',
            'title' => 'nullable|string',
            'days' => 'required|integer|min:1|max:31',
            'rate' => 'required|integer|min:0',
            'quota' => 'required|integer|min:0',
            'decree_total' => 'nullable|integer|min:0',
            'start_date' => 'nullable|date',
            'note_number' => 'nullable|string|max:120',
            'position' => 'nullable|integer|min:0',
            'active' => 'nullable|boolean',
            'exam_id' => 'nullable|integer|exists:exams,id',
            'sg_id' => 'nullable|array',
            'sg_name' => 'nullable|array',
            'sg_planned' => 'nullable|array',
            'sg_planned.*' => 'nullable|integer|min:0',
        ];
    }

    public function index()
    {
        $exam = request('exam');
        $q = Activity::with(['exam', 'groups'])->orderBy('position')->orderBy('id');
        if ($exam === 'none') $q->whereNull('exam_id');
        elseif ($exam) $q->where('exam_id', (int) $exam);
        return view('activities.index', ['activities' => $q->get(), 'exams' => Exam::orderBy('position')->orderBy('code')->get(), 'examFilter' => $exam]);
    }

    public function create()
    {
        $a = new Activity(['days' => 10, 'rate' => 36000, 'quota' => 0, 'active' => true, 'position' => (Activity::max('position') ?? 0) + 1]);
        return view('activities.form', ['a' => $a, 'exams' => Exam::orderBy('position')->orderBy('code')->get()]);
    }

    public function store(Request $r)
    {
        $activity = Activity::create($this->data($r));
        $this->syncGroups($activity, $r);
        return redirect()->route('activities.index')->with('ok', 'Activité ajoutée. Relancez la répartition pour la prendre en compte.');
    }

    public function edit(Activity $activity)
    {
        return view('activities.form', ['a' => $activity, 'exams' => Exam::orderBy('position')->orderBy('code')->get()]);
    }

    public function update(Request $r, Activity $activity)
    {
        $activity->update($this->data($r));
        $this->syncGroups($activity, $r);
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

        $d['exam_id'] = $d['exam_id'] ?? null;

        // ---- sous-groupes libres : la somme des effectifs prévus ne dépasse pas le nombre d'agents de l'activité
        $cap = (int) ($d['decree_total'] > 0 ? $d['decree_total'] : $d['quota']);
        $sum = 0;
        foreach ((array) $r->input('sg_name', []) as $i => $name) {
            if (trim((string) $name) !== '') $sum += (int) ($r->input('sg_planned', [])[$i] ?? 0);
        }
        if ($cap > 0 && $sum > $cap) {
            throw ValidationException::withMessages(['sg' => "La somme des sous-groupes ($sum) dépasse le nombre total d’agents de l’activité ($cap)."]);
        }
        unset($d['sg_id'], $d['sg_name'], $d['sg_planned']);
        return $d;
    }

    /** Enregistre la liste des sous-groupes du formulaire : ajoute, renomme, réordonne, supprime. */
    private function syncGroups(Activity $activity, Request $r): void
    {
        $ids = (array) $r->input('sg_id', []);
        $names = (array) $r->input('sg_name', []);
        $planned = (array) $r->input('sg_planned', []);
        $keep = [];
        $pos = 0;
        foreach ($names as $i => $name) {
            $name = trim((string) $name);
            if ($name === '') continue;
            $data = ['name' => $name, 'planned' => max(0, (int) ($planned[$i] ?? 0)), 'position' => ++$pos];
            $id = (int) ($ids[$i] ?? 0);
            $g = $id ? ActivitySubgroup::where('activity_id', $activity->id)->find($id) : null;
            if ($g) { $g->update($data); } else { $g = ActivitySubgroup::create($data + ['activity_id' => $activity->id]); }
            $keep[] = $g->id;
        }
        // sous-groupes supprimés : leurs agents redeviennent « non répartis »
        $removed = ActivitySubgroup::where('activity_id', $activity->id)->whereNotIn('id', $keep)->pluck('id');
        if ($removed->count()) {
            AgentSubgroup::where('activity_id', $activity->id)->whereIn('subgroup', $removed->map(fn($i) => (string) $i)->all())->delete();
            ActivitySubgroup::whereIn('id', $removed)->delete();
        }
    }
}

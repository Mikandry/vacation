<?php
namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Exam;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    private function rules(): array
    {
        return [
            'code' => 'required|string|max:40',
            'name' => 'required|string|max:255',
            'session' => 'nullable|string|max:60',
            'ref_arrete' => 'nullable|string',
            'decision_number' => 'nullable|string|max:120',
            'position' => 'nullable|integer|min:0',
        ];
    }

    public function index()
    {
        return view('exams.index', ['exams' => Exam::withCount('activities')->orderBy('position')->orderBy('code')->get()]);
    }

    public function store(Request $r)
    {
        $d = $r->validate($this->rules());
        $d['position'] = $d['position'] ?? (Exam::max('position') ?? 0) + 1;
        Exam::create($d);
        return redirect()->route('exams.index')->with('ok', 'Examen ajouté.');
    }

    public function update(Request $r, Exam $exam)
    {
        $d = $r->validate($this->rules());
        $d['position'] = $d['position'] ?? $exam->position;
        $exam->update($d);
        return redirect()->route('exams.index')->with('ok', 'Examen modifié.');
    }

    public function destroy(Exam $exam)
    {
        Activity::where('exam_id', $exam->id)->update(['exam_id' => null]);   // les activités restent, sans examen
        $exam->delete();
        return redirect()->route('exams.index')->with('ok', 'Examen supprimé (ses activités sont conservées).');
    }
}

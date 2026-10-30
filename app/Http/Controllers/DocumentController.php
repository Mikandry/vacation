<?php
namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Exam;
use App\Models\Setting;
use App\Services\DocumentExporter;
use App\Services\DocumentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function index()
    {
        $exam = request('exam');
        $q = Activity::with(['exam', 'groups'])->withCount('assignments')->orderBy('position')->orderBy('id');
        if ($exam === 'none') $q->whereNull('exam_id');
        elseif ($exam) $q->where('exam_id', (int) $exam);
        return view('docs.index', [
            'acts' => $q->get(), 'irsa' => Setting::all_()['irsa_rate'],
            'exams' => Exam::orderBy('position')->orderBy('code')->get(), 'examFilter' => $exam,
        ]);
    }

    /** Page d'aperçu : le PDF s'affiche dans la page, avec les options et les boutons de téléchargement. */
    public function preview(Request $r, Activity $activity, string $type)
    {
        abort_unless(isset(DocumentExporter::TITLES[$type]), 404);
        $s = Setting::all_();
        return view('docs.preview', [
            'a' => $activity,
            'type' => $type,
            'title' => DocumentExporter::TITLES[$type],
            'irsa' => $r->query('irsa', $s['irsa_rate']),
            'showDates' => $s['presence_show_dates'] === 'OUI',
        ]);
    }

    /**
     * /documents/{activity}/{type}/{format}
     * type : decision|note|presence|etat|decompte    format : pdf|docx|xlsx
     * Options : ?irsa=15   ?dates=0   ?inline=1 (affiche le PDF au lieu de le télécharger)
     */
    public function export(Request $r, Activity $activity, string $type, string $format, DocumentService $docs, DocumentExporter $ex)
    {
        abort_unless(isset(DocumentExporter::TITLES[$type]) && in_array($format, ['pdf', 'docx', 'xlsx']), 404);
        abort_if($type === 'note' && $format === 'xlsx', 404);

        $opt = [];
        if ($r->filled('irsa')) $opt['irsa'] = $r->query('irsa');
        if ($r->has('dates')) $opt['dates'] = $r->boolean('dates');

        $d = $docs->data($activity, $opt);
        if ($format === 'docx') return $ex->word($type, $d);
        if ($format === 'xlsx') return $ex->excel($type, $d);

        $d['docs'] = $docs;
        $pdf = Pdf::loadView("docs.$type", $d)
            ->setPaper('a4', $type === 'presence' ? 'landscape' : 'portrait');
        $name = "{$type}-{$d['file']}.pdf";
        $resp = $r->boolean('inline') ? $pdf->stream($name) : $pdf->download($name);
        return $resp->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')->header('Pragma', 'no-cache');
    }
}
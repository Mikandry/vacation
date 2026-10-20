<?php
namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Setting;
use App\Services\DocumentExporter;
use App\Services\DocumentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function index()
    {
        $acts = Activity::withCount('assignments')->orderBy('position')->orderBy('id')->get();
        return view('docs.index', ['acts' => $acts, 'irsa' => Setting::all_()['irsa_rate']]);
    }

    /**
     * /documents/{activity}/{type}/{format}
     * type : decision|note|presence|etat|decompte    format : pdf|docx|xlsx
     * Options (query string) : ?irsa=15  (taux IRSA)   ?dates=0  (présence sans dates)
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
        return Pdf::loadView("docs.$type", $d)
            ->setPaper('a4', $type === 'presence' ? 'landscape' : 'portrait')
            ->download("{$type}-{$d['file']}.pdf");
    }
}
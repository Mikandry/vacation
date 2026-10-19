<?php
namespace App\Http\Controllers;

use App\Models\Activity;
use App\Services\DocumentExporter;
use App\Services\DocumentService;
use Barryvdh\DomPDF\Facade\Pdf;

class DocumentController extends Controller
{
    public function index()
    {
        $acts = Activity::withCount('assignments')->orderBy('position')->orderBy('id')->get();
        return view('docs.index', ['acts' => $acts]);
    }

    /** /documents/{activity}/{type}/{format}  type: decision|presence|etat   format: pdf|docx|xlsx */
    public function export(Activity $activity, string $type, string $format, DocumentService $docs, DocumentExporter $ex)
    {
        abort_unless(isset(DocumentExporter::TITLES[$type]) && in_array($format, ['pdf', 'docx', 'xlsx']), 404);
        $d = $docs->data($activity);
        if ($format === 'docx') return $ex->word($type, $d);
        if ($format === 'xlsx') return $ex->excel($type, $d);
        $d['docs'] = $docs;
        return Pdf::loadView("docs.$type", $d)->setPaper('a4', $type === 'presence' ? 'landscape' : 'portrait')->download("{$type}-{$d['file']}.pdf");
    }
}

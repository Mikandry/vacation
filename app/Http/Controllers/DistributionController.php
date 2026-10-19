<?php
namespace App\Http\Controllers;

use App\Services\BalanceService;
use App\Services\DistributionService;
use App\Services\DocumentExporter;

class DistributionController extends Controller
{
    public function index(BalanceService $svc) { return view('distribution.index', ['b' => $svc->compute()]); }

    public function run(DistributionService $d)
    {
        $n = $d->run();
        return redirect()->route('dashboard')->with('ok', "Répartition recalculée : $n affectations.");
    }
    public function redraw(DistributionService $d)
    {
        $d->redraw(); $n = $d->run();
        return back()->with('ok', "Nouveau tirage de départage effectué et répartition recalculée ($n affectations).");
    }
    public function archive(DistributionService $d)
    {
        $d->archive();
        return redirect()->route('distribution.index')->with('ok', 'Session clôturée : les montants ont été ajoutés à l’historique des agents et les affectations ont été vidées.');
    }
    public function recap(BalanceService $svc, DocumentExporter $ex, string $format)
    {
        $b = $svc->compute();
        if ($format === 'xlsx') return $ex->recapExcel($b);
        abort_unless($format === 'pdf', 404);
        return \Barryvdh\DomPDF\Facade\Pdf::loadView('docs.recap', $b)->setPaper('a4', 'landscape')->download('recapitulatif-agents.pdf');
    }
}

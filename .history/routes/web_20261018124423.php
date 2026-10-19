<?php
use App\Http\Controllers\{ActivityController, AgentController, DashboardController, DistributionController, DocumentController, ExceptionController, SettingController, VisaController};
use Illuminate\Support\Facades\Route;

// Pour protéger l'application : installer Laravel Breeze puis remplacer [] par ['auth'].
Route::middleware([])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/repartition', [DistributionController::class, 'index'])->name('distribution.index');
    Route::post('/repartition/run', [DistributionController::class, 'run'])->name('distribution.run');
    Route::post('/repartition/redraw', [DistributionController::class, 'redraw'])->name('distribution.redraw');
    Route::post('/repartition/archive', [DistributionController::class, 'archive'])->name('distribution.archive');
    Route::get('/repartition/recap.{format}', [DistributionController::class, 'recap'])->name('distribution.recap');

    Route::resource('activities', ActivityController::class)->except('show');

    Route::get('/agents/import', [AgentController::class, 'importForm'])->name('agents.import.form');
    Route::post('/agents/import', [AgentController::class, 'import'])->name('agents.import');
    Route::get('/agents/export', [AgentController::class, 'export'])->name('agents.export');
    Route::resource('agents', AgentController::class)->except('show');

    Route::get('/exceptions', [ExceptionController::class, 'index'])->name('exceptions.index');
    Route::post('/exceptions', [ExceptionController::class, 'set'])->name('exceptions.set');

    Route::get('/parametres', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/parametres', [SettingController::class, 'update'])->name('settings.update');
    Route::get('/visas', [VisaController::class, 'index'])->name('visas.index');
    Route::post('/visas', [VisaController::class, 'update'])->name('visas.update');

    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::get('/documents/{activity}/{type}/{format}', [DocumentController::class, 'export'])->name('documents.export');
    Route::get('/choix', [ManualController::class, 'matrix'])->name('choix.matrix');
Route::post('/choix/toggle', [ManualController::class, 'toggle'])->name('choix.toggle');
Route::post('/choix/appliquer', [ManualController::class, 'apply'])->name('choix.apply');
Route::get('/choix/{activity}', [ManualController::class, 'show'])->name('choix.show');
Route::post('/choix/{activity}', [ManualController::class, 'save'])->name('choix.save');
Route::post('/choix/{activity}/externe', [ManualController::class, 'addExternal'])->name('choix.external');
Route::post('/choix/{activity}/auto', [ManualController::class, 'auto'])->name('choix.auto');

    });

<?php
namespace App\Http\Controllers;

use App\Models\Visa;
use Illuminate\Http\Request;

class VisaController extends Controller
{
    public function index() { return view('visas', ['visas' => Visa::orderBy('position')->orderBy('id')->get()]); }
    public function update(Request $r)
    {
        $items = collect($r->input('visas', []))->filter(fn($v) => trim($v['text'] ?? '') !== '' && empty($v['delete']))->values();
        Visa::query()->delete();
        foreach ($items as $i => $v) Visa::create(['text' => trim($v['text']), 'included' => !empty($v['included']), 'position' => $i + 1]);
        return redirect()->route('visas.index')->with('ok', 'Visas enregistrés.');
    }
}

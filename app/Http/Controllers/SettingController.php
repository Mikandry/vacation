<?php
namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index() { return view('settings', ['s' => Setting::all_()]); }
    public function update(Request $r)
    {
        $r->validate(['tolerance' => 'required|numeric|min:0|max:100', 'threshold_ok' => 'required|numeric|min:0|max:100', 'threshold_acc' => 'required|numeric|min:0|max:100',
            'band_step' => 'required|integer|min:1000', 'auto' => 'required|in:OUI,NON', 'use_history' => 'required|in:OUI,NON']);
        Setting::put($r->only(array_keys(Setting::DEFAULTS)));
        return redirect()->route('settings.index')->with('ok', 'Paramètres enregistrés. Relancez la répartition si nécessaire.');
    }
}

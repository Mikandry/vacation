<?php
namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index() { return view('settings', ['s' => Setting::all_()]); }

    public function update(Request $r)
    {
        $r->validate([
            'tolerance' => 'required|numeric|min:0|max:100',
            'threshold_ok' => 'required|numeric|min:0|max:100',
            'threshold_acc' => 'required|numeric|min:0|max:100',
            'band_step' => 'required|integer|min:1000',
            'auto' => 'required|in:OUI,NON',
            'use_history' => 'required|in:OUI,NON',
            'avoid_overlap' => 'required|in:OUI,NON',
            'irsa_rate' => 'required|numeric|min:0|max:100',
            'presence_max_cols' => 'required|integer|min:1|max:31',
            'presence_row_height' => 'required|integer|min:18|max:120',
            'presence_id_width' => 'required|integer|min:25|max:60',
            'presence_show_dates' => 'required|in:OUI,NON',
            'decompte_rows' => 'required|integer|min:5|max:60',
        ]);
        Setting::put($r->only(array_keys(Setting::DEFAULTS)));
        return redirect()->route('settings.index')->with('ok', 'Paramètres enregistrés. Relancez la répartition si nécessaire.');
    }
}
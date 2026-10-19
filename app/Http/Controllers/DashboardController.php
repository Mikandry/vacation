<?php
namespace App\Http\Controllers;
use App\Services\BalanceService;

class DashboardController extends Controller
{
    public function index(BalanceService $svc) { return view('dashboard', ['b' => $svc->compute()]); }
}

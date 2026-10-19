<?php
namespace App\Services;

use App\Models\Activity;
use App\Models\Agent;
use App\Models\Setting;
use Illuminate\Support\Str;

class BalanceService
{
    public static function services(array $s): array
    {
        $out = [];
        foreach (preg_split('/\R/', trim($s['services'])) as $line) {
            if (trim($line) === '') {
                continue;
            }
            [$code, $name] = array_pad(explode('|', $line, 2), 2, '');
            $out[trim($code)] = trim($name);
        }
        return $out;
    }

    public function compute(): array
    {
        $s = Setting::all_();
        $hist = $s['use_history'] === 'OUI';
        $tol = (float) $s['tolerance'] / 100;

        $acts = Activity::where('active', true)->orderBy('position')->orderBy('id')->get();
        $actMap = $acts->keyBy('id');
        $agents = Agent::with('assignments')->get()
            ->sortBy(fn($a) => Str::upper(Str::ascii($a->name)))
            ->values();

        // ---------- une ligne par agent ----------
        $rows = [];
        foreach ($agents as $a) {
            $mine = $a->assignments->filter(fn($x) => $actMap->has($x->activity_id));
            $amount = $mine->sum(fn($x) => $actMap[$x->activity_id]->amount);
            $rows[] = [
                'agent'  => $a,
                'ids'    => $mine->pluck('activity_id')->all(),
                'acts'   => $mine->map(fn($x) => $actMap[$x->activity_id])->values(),
                'count'  => $mine->count(),
                'amount' => $amount,
                'total'  => $amount + ($hist ? (int) $a->history_amount : 0),
                'base'   => $a->type === 'DIRECTION' && $a->available,
            ];
        }

        $base = collect($rows)->where('base', true);
        $n = $base->count();
        $mean = $n ? $base->avg('total') : 0;
        $min = $n ? $base->min('total') : 0;
        $max = $n ? $base->max('total') : 0;
        $std = $n ? sqrt($base->avg(fn($r) => ($r['total'] - $mean) ** 2)) : 0;

        // ---------- statut de chaque agent ----------
        foreach ($rows as &$r) {
            if (!$r['agent']->available) {
                $r['status'] = 'Indisponible';
                $r['dev'] = null;
            } elseif ($r['agent']->type !== 'DIRECTION') {
                $r['status'] = 'Externe';
                $r['dev'] = null;
            } elseif ($mean == 0) {
                $r['status'] = '-';
                $r['dev'] = 0;
            } else {
                $r['dev'] = $r['total'] / $mean - 1;
                if (abs($r['dev']) <= $tol) {
                    $r['status'] = 'Équilibré';
                } elseif ($r['dev'] > 0) {
                    $r['status'] = 'Sur-chargé';
                } else {
                    $r['status'] = 'Sous-chargé';
                }
            }
        }
        unset($r);

        $inTol = collect($rows)->where('status', 'Équilibré')->count();
        $pct = $n ? $inTol / $n * 100 : 0;

        if ($n === 0) {
            $verdict = '—';
        } elseif ($pct >= $s['threshold_ok']) {
            $verdict = 'ÉQUILIBRÉ';
        } elseif ($pct >= $s['threshold_acc']) {
            $verdict = 'ACCEPTABLE';
        } else {
            $verdict = 'DÉSÉQUILIBRÉ';
        }

        // ---------- par activité ----------
        $perAct = [];
        foreach ($acts as $act) {
            $assigned = collect($rows)->filter(fn($r) => in_array($act->id, $r['ids']));
            $dir = $assigned->where('base', true)->count();
            $tot = $assigned->count();

            if ($act->manual) {
                $check = '✎ Choix manuel';
            } elseif ($act->quota == 0) {
                $check = $tot > $dir ? 'Quota à définir (forcés seuls)' : '⚠ Quota à définir';
            } elseif ($dir > $act->quota) {
                $check = '⚠ Dépasse le quota (forçages)';
            } elseif ($dir < $act->quota) {
                $check = '⚠ Quota non atteint';
            } else {
                $check = '✔ OK';
            }

            $perAct[] = [
                'activity'   => $act,
                'quota'      => $act->quota,
                'dir'        => $dir,
                'ext'        => $tot - $dir,
                'total'      => $tot,
                'decree'     => $act->decree_total,
                'gap_decree' => $tot - $act->decree_total,
                'money'      => $tot * $act->amount,
                'check'      => $check,
            ];
        }

        // ---------- par service ----------
        $perSvc = [];
        foreach (self::services($s) as $code => $name) {
            $g = $base->filter(fn($r) => $r['agent']->locality === $code);
            $avg = $g->count() ? $g->avg('total') : 0;
            $dev = $mean ? $avg / $mean - 1 : 0;

            if ($g->count() === 0) {
                $status = '—';
            } elseif (abs($dev) <= $tol / 2) {
                $status = '✔ Équilibré';
            } elseif ($dev > 0) {
                $status = '⚠ Plus chargé';
            } else {
                $status = '⚠ Moins chargé';
            }

            $perSvc[] = [
                'code'   => $code,
                'name'   => $name,
                'n'      => $g->count(),
                'assign' => $g->sum('count'),
                'sum'    => $g->sum('total'),
                'avg'    => $avg,
                'dev'    => $dev,
                'status' => $status,
            ];
        }
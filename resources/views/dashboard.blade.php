@extends('layouts.app')
@section('title','Tableau de bord')
@php
 $st  = $b['stats'];
 $fmt = fn($x) => number_format((float) $x, 0, ',', ' ');
 $vmap = ['ÉQUILIBRÉ' => ['success', 'bi-check-circle-fill'], 'ACCEPTABLE' => ['warning', 'bi-exclamation-circle-fill'], 'DÉSÉQUILIBRÉ' => ['danger', 'bi-x-circle-fill'], '—' => ['secondary', 'bi-dash-circle']];
 [$vc, $vi] = $vmap[$b['verdict']] ?? ['secondary', 'bi-dash-circle'];
 $nIssues = collect($b['checks'])->filter(fn($c) => $c[1] > 0 && $c[2] === 'warn')->count();

 $cards = [
  ['Agents mobilisables', $st['n'], 'bi-people-fill', '#e3f0ff', '#1f5fae'],
  ['Affectations (direction)', $st['assignments'], 'bi-diagram-3-fill', '#e8f5e9', '#2e7d32'],
  ['Montant total (Ar)', $fmt($st['sum']), 'bi-cash-stack', '#fff6dd', '#b8860b'],
  ['Moyenne / agent (Ar)', $fmt($st['mean']), 'bi-calculator-fill', '#f3e5f5', '#6a1b9a'],
  ['Minimum (Ar)', $fmt($st['min']), 'bi-arrow-down-circle-fill', '#e0f7fa', '#00838f'],
  ['Maximum (Ar)', $fmt($st['max']), 'bi-arrow-up-circle-fill', '#fff3e0', '#e65100'],
  ['Étendue (Ar)', $fmt($st['range']), 'bi-arrows-expand', '#eceff1', '#455a64'],
  ['Coefficient de variation', number_format($st['cv'] * 100, 1, ',', ' ') . ' %', 'bi-graph-up', '#fce4ec', '#ad1457'],
  ['Sur-chargés', $st['over'], 'bi-emoji-frown-fill', '#ffebee', '#c62828'],
  ['Sous-chargés', $st['under'], 'bi-emoji-neutral-fill', '#fff8e1', '#ef6c00'],
  ['Sans aucune activité', $st['none'], 'bi-person-x-fill', '#fbe9e7', '#bf360c'],
 ];

 $chart1 = ['labels' => collect($b['bands'])->pluck('label')->all(), 'data' => collect($b['bands'])->pluck('count')->all()];
 $chart2 = ['labels' => collect($b['byCount'])->pluck('label')->all(), 'data' => collect($b['byCount'])->pluck('count')->all()];
 $chart3 = ['labels' => collect($b['perAct'])->map(fn($x) => $x['activity']->code)->all(), 'data' => collect($b['perAct'])->pluck('money')->all()];
 $chart4 = ['labels' => collect($b['perSvc'])->pluck('code')->all(), 'data' => collect($b['perSvc'])->map(fn($x) => round($x['avg']))->all()];
@endphp

@section('content')
<div class="card mb-3 border-start border-4 border-{{ $vc }}">
 <div class="card-body d-flex flex-wrap align-items-center gap-3">
  <i class="bi {{ $vi }} text-{{ $vc }}" style="font-size:2.4rem"></i>
  <div>
   <div class="text-muted small text-uppercase">Équilibre de la répartition — session {{ $b['settings']['session'] }}</div>
   <div class="fs-3 fw-bold text-{{ $vc }}">{{ $b['verdict'] }}</div>
   <div class="small text-muted">{{ round($st['pct']) }} % des agents sont à ± {{ $b['settings']['tolerance'] }} % de la moyenne</div>
  </div>
  <div class="ms-auto d-flex flex-wrap gap-2">
   <form method="post" action="{{ route('distribution.run') }}">@csrf <button class="btn btn-primary"><i class="bi bi-arrow-repeat"></i> Relancer la répartition</button></form>
   <a class="btn btn-outline-primary" href="{{ route('choix.matrix') }}"><i class="bi bi-hand-index-thumb"></i> Choix manuel</a>
   <a class="btn btn-outline-success" href="{{ route('documents.index') }}"><i class="bi bi-file-earmark-text"></i> Documents</a>
  </div>
 </div>
</div>

@if($b['conflicts'] > 0)
<div class="alert alert-danger d-flex align-items-center gap-3">
 <i class="bi bi-exclamation-triangle-fill fs-4"></i>
 <div><b>{{ $b['conflicts'] }}</b> agent(s) sont désignés dans deux activités dont les dates se chevauchent.</div>
 <a class="btn btn-danger btn-sm ms-auto" href="{{ route('conflicts.index') }}">Résoudre les conflits</a>
</div>
@endif

<div class="row g-2 mb-3">
 @foreach($cards as [$label, $value, $icon, $bg, $fg])
  <div class="col-6 col-md-4 col-xl-3 col-xxl-2">
   <div class="kpi"><div class="ic" style="background:{{ $bg }};color:{{ $fg }}"><i class="bi {{ $icon }}"></i></div>
    <div><div class="l">{{ $label }}</div><div class="v">{{ $value }}</div></div></div>
  </div>
 @endforeach
</div>

<div class="row g-3">
 <div class="col-xl-8">
  <div class="card mb-3">
   <div class="card-header"><i class="bi bi-list-check"></i> Par activité — quotas, effectifs et montants</div>
   <div class="table-responsive">
    <table class="table table-sm table-hover mb-0 text-center align-middle">
     <thead class="table-dark"><tr><th class="text-start">Activité</th><th>Jours</th><th>Montant/agent</th><th>Quota</th><th>Direction</th><th>Externes</th><th>Total</th><th>Décret</th><th>Écart</th><th>Montant total</th><th class="text-start">Contrôle</th></tr></thead>
     @foreach($b['perAct'] as $x)
     <tr>
      <td class="text-start fw-bold"><a href="{{ route('choix.show', $x['activity']) }}">{{ $x['activity']->code }}</a></td>
      <td>{{ $x['activity']->days }}</td><td>{{ $fmt($x['activity']->amount) }}</td><td>{{ $x['quota'] }}</td><td>{{ $x['dir'] }}</td><td>{{ $x['ext'] }}</td><td>{{ $x['total'] }}</td><td>{{ $x['decree'] }}</td><td>{{ $x['gap_decree'] }}</td><td>{{ $fmt($x['money']) }}</td>
      <td class="text-start {{ str_starts_with($x['check'], '⚠') ? 'text-danger fw-bold' : (str_starts_with($x['check'], '✔') ? 'text-success fw-bold' : '') }}">{{ $x['check'] }}</td>
     </tr>
     @endforeach
     <tr class="table-light fw-bold"><td class="text-start">TOTAL</td><td></td><td></td><td>{{ collect($b['perAct'])->sum('quota') }}</td><td>{{ collect($b['perAct'])->sum('dir') }}</td><td>{{ collect($b['perAct'])->sum('ext') }}</td><td>{{ collect($b['perAct'])->sum('total') }}</td><td>{{ collect($b['perAct'])->sum('decree') }}</td><td></td><td>{{ $fmt(collect($b['perAct'])->sum('money')) }}</td><td></td></tr>
    </table>
   </div>
  </div>

  <div class="card mb-3">
   <div class="card-header"><i class="bi bi-building"></i> Par service — la charge est-elle équitable ?</div>
   <div class="table-responsive">
    <table class="table table-sm table-hover mb-0 text-center align-middle">
     <thead class="table-dark"><tr><th class="text-start">Service</th><th>Effectif</th><th>Affectations</th><th>Montant total</th><th>Moyenne / agent</th><th>Écart</th><th class="text-start">Statut</th></tr></thead>
     @foreach($b['perSvc'] as $x)
     <tr><td class="text-start">{{ $x['code'] }} <span class="text-muted small">{{ $x['name'] }}</span></td><td>{{ $x['n'] }}</td><td>{{ $x['assign'] }}</td><td>{{ $fmt($x['sum']) }}</td><td>{{ $fmt($x['avg']) }}</td><td>{{ number_format($x['dev'] * 100, 1, ',', ' ') }} %</td>
      <td class="text-start {{ str_starts_with($x['status'], '⚠') ? 'text-danger fw-bold' : 'text-success fw-bold' }}">{{ $x['status'] }}</td></tr>
     @endforeach
    </table>
   </div>
  </div>
 </div>

 <div class="col-xl-4">
  <div class="card mb-3">
   <div class="card-header d-flex align-items-center">
    <span><i class="bi bi-shield-check"></i> Contrôles</span>
    @if($nIssues > 0)<span class="badge text-bg-danger ms-auto">{{ $nIssues }} à résoudre</span>@else<span class="badge text-bg-success ms-auto">Tout est OK</span>@endif
   </div>
   <ul class="list-group list-group-flush">
    @foreach($b['checks'] as [$label, $n, $kind, $key])
     <li class="list-group-item d-flex align-items-center gap-2 small">
      <span class="me-auto">{{ $label }}</span>
      @if($n == 0)
       <span class="text-success fw-bold"><i class="bi bi-check-circle-fill"></i> OK</span>
      @else
       <span class="badge text-bg-{{ $kind === 'warn' ? 'danger' : 'info' }}">{{ $n }}</span>
       <a href="{{ route('issues.show', $key) }}" class="btn btn-sm {{ $kind === 'warn' ? 'btn-danger' : 'btn-outline-primary' }} py-0">Voir / résoudre <i class="bi bi-arrow-right"></i></a>
      @endif
     </li>
    @endforeach
   </ul>
  </div>
  <div class="card mb-3"><div class="card-body"><canvas id="c1"></canvas></div></div>
  <div class="card mb-3"><div class="card-body"><canvas id="c2"></canvas></div></div>
 </div>

 <div class="col-md-6"><div class="card"><div class="card-body"><canvas id="c3"></canvas></div></div></div>
 <div class="col-md-6"><div class="card"><div class="card-body"><canvas id="c4"></canvas></div></div></div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
const mk = (id, title, d, color) => new Chart(document.getElementById(id), {
 type: 'bar',
 data: { labels: d.labels, datasets: [{ data: d.data, backgroundColor: color, borderRadius: 4 }] },
 options: { plugins: { legend: { display: false }, title: { display: true, text: title } } }
});
mk('c1', 'Agents par tranche de montant (milliers Ar)', {!! json_encode($chart1) !!}, '#2e7d32');
mk('c2', 'Agents par nombre d’activités', {!! json_encode($chart2) !!}, '#6a1b9a');
mk('c3', 'Montant total par activité (Ar)', {!! json_encode($chart3) !!}, '#1f3a5f');
mk('c4', 'Moyenne par agent et par service (Ar)', {!! json_encode($chart4) !!}, '#d4a017');
</script>
@endpush

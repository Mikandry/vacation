@extends('layouts.app')
@section('title','Tableau de bord')
@php
 $st=$b['stats']; $fmt=fn($x)=>number_format((float)$x,0,',',' ');
 $vc=['ÉQUILIBRÉ'=>'success','ACCEPTABLE'=>'warning','DÉSÉQUILIBRÉ'=>'danger','—'=>'secondary'][$b['verdict']];
@endphp
@section('content')
<div class="d-flex flex-wrap align-items-center gap-3 mb-3">
 <h4 class="mb-0">Tableau de bord — session {{ $b['settings']['session'] }}</h4>
 <span class="badge text-bg-{{ $vc }} fs-5">{{ $b['verdict'] }}</span>
 <span class="text-muted small">{{ round($st['pct']) }} % des agents sont à ± {{ $b['settings']['tolerance'] }} % de la moyenne</span>
 <form method="post" action="{{ route('distribution.run') }}" class="ms-auto">@csrf <button class="btn btn-primary">⟳ Relancer la répartition</button></form>
</div>
<div class="row g-2 mb-3">
 @foreach([['Agents mobilisables',$st['n']],['Affectations (direction)',$st['assignments']],['Montant total (Ar)',$fmt($st['sum'])],['Moyenne / agent (Ar)',$fmt($st['mean'])],['Minimum (Ar)',$fmt($st['min'])],['Maximum (Ar)',$fmt($st['max'])],
  ['Étendue (Ar)',$fmt($st['range'])],['Coeff. de variation',number_format($st['cv']*100,1,',',' ').' %'],['Sur-chargés',$st['over']],['Sous-chargés',$st['under']],['Sans activité',$st['none']]] as [$l,$v])
  <div class="col-6 col-md-3 col-xl-2"><div class="kpi"><div class="l">{{ $l }}</div><div class="v">{{ $v }}</div></div></div>
 @endforeach
</div>
<div class="row g-3">
 <div class="col-xl-8">
  <div class="card mb-3"><div class="card-header fw-bold">Par activité</div><div class="table-responsive">
   <table class="table table-sm table-bordered mb-0 text-center">
    <thead class="table-dark"><tr><th>Activité</th><th>Jours</th><th>Montant/agent</th><th>Quota</th><th>Direction</th><th>Externes</th><th>Total</th><th>Décret</th><th>Écart décret</th><th>Montant total</th><th class="text-start">Contrôle</th></tr></thead>
    @foreach($b['perAct'] as $x)<tr>
     {{-- <td class="text-start fw-bold">{{ $x['activity']->code }}</td><td>{{ $x['activity']->days }}</td><td>{{ $fmt($x['activity']->amount) }}</td><td>{{ $x['quota'] }}</td><td>{{ $x['dir'] }}</td><td>{{ $x['ext'] }}</td><td>{{ $x['total'] }}</td><td>{{ $x['decree'] }}</td><td>{{ $x['gap_decree'] }}</td><td>{{ $fmt($x['money']) }}</td> --}}
     <td class="text-start fw-bold"><a href="{{ route('choix.show',$x['activity']) }}">{{ $x['activity']->code }}</a></td>
     <td class="text-start {{ str_starts_with($x['check'],'⚠')?'text-danger fw-bold':(str_starts_with($x['check'],'✔')?'text-success fw-bold':'') }}">{{ $x['check'] }}</td></tr>@endforeach
    <tr class="table-light fw-bold"><td class="text-start">TOTAL</td><td></td><td></td><td>{{ collect($b['perAct'])->sum('quota') }}</td><td>{{ collect($b['perAct'])->sum('dir') }}</td><td>{{ collect($b['perAct'])->sum('ext') }}</td><td>{{ collect($b['perAct'])->sum('total') }}</td><td>{{ collect($b['perAct'])->sum('decree') }}</td><td></td><td>{{ $fmt(collect($b['perAct'])->sum('money')) }}</td><td></td></tr>
   </table></div></div>
  <div class="card mb-3"><div class="card-header fw-bold">Par service — la charge est-elle équitable ?</div><div class="table-responsive">
   <table class="table table-sm table-bordered mb-0 text-center"><thead class="table-dark"><tr><th class="text-start">Service</th><th>Effectif</th><th>Affectations</th><th>Montant total</th><th>Moyenne / agent</th><th>Écart / moyenne</th><th class="text-start">Statut</th></tr></thead>
   @foreach($b['perSvc'] as $x)<tr><td class="text-start">{{ $x['code'] }} <span class="text-muted small">{{ $x['name'] }}</span></td><td>{{ $x['n'] }}</td><td>{{ $x['assign'] }}</td><td>{{ $fmt($x['sum']) }}</td><td>{{ $fmt($x['avg']) }}</td><td>{{ number_format($x['dev']*100,1,',',' ') }} %</td>
    <td class="text-start {{ str_starts_with($x['status'],'⚠')?'text-danger fw-bold':'text-success fw-bold' }}">{{ $x['status'] }}</td></tr>@endforeach</table></div></div>
 </div>
 <div class="col-xl-4">
  <div class="card mb-3"><div class="card-header fw-bold">Contrôles</div><ul class="list-group list-group-flush">
   @foreach($b['checks'] as [$l,$n,$k])<li class="list-group-item d-flex justify-content-between small"><span>{{ $l }}</span>
    <span class="fw-bold {{ $n==0?'text-success':($k=='warn'?'text-danger':'text-primary') }}">{{ $n==0?'✔ OK':$n }}</span></li>@endforeach</ul></div>
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
const mk=(id,title,labels,data,color)=>new Chart(document.getElementById(id),{type:'bar',data:{labels,datasets:[{data,backgroundColor:color}]},options:{plugins:{legend:{display:false},title:{display:true,text:title}}}});
mk('c1','Agents par tranche de montant (milliers Ar)',@json(collect($b['bands'])->pluck('label')),@json(collect($b['bands'])->pluck('count')),'#2e7d32');
mk('c2','Agents par nombre d’activités',@json(collect($b['byCount'])->pluck('label')),@json(collect($b['byCount'])->pluck('count')),'#6a1b9a');
mk('c3','Montant total par activité (Ar)',@json(collect($b['perAct'])->map(fn($x)=>$x['activity']->code)),@json(collect($b['perAct'])->pluck('money')),'#1f3a5f');
mk('c4','Moyenne par agent et par service (Ar)',@json(collect($b['perSvc'])->pluck('code')),@json(collect($b['perSvc'])->map(fn($x)=>round($x['avg']))),'#ef6c00');
</script>
@endpush

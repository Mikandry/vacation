@extends('layouts.app')
@section('title','Répartition')
@php $fmt=fn($x)=>number_format((float)$x,0,',',' '); @endphp
@section('content')
<div class="d-flex flex-wrap gap-2 align-items-center mb-3">
 <h4 class="mb-0 me-3">Résultat de la répartition</h4>
 <form method="post" action="{{ route('distribution.run') }}">@csrf <button class="btn btn-primary">⟳ Recalculer</button></form>
 <form method="post" action="{{ route('distribution.redraw') }}" onsubmit="return confirm('Refaire le tirage de départage et recalculer ?')">@csrf <button class="btn btn-outline-secondary">🎲 Nouveau tirage</button></form>
 <a class="btn btn-outline-success" href="{{ route('distribution.recap','xlsx') }}">Récap Excel</a>
 <a class="btn btn-outline-danger" href="{{ route('distribution.recap','pdf') }}">Récap PDF</a>
 <form method="post" action="{{ route('distribution.archive') }}" class="ms-auto" onsubmit="return confirm('Clôturer la session : ajouter les montants à l’historique et vider les affectations ?')">@csrf <button class="btn btn-outline-dark">Clôturer la session (historique)</button></form>
</div>
<div class="table-responsive" style="max-height:75vh">
<table class="table table-sm table-bordered table-hover bg-white sticky">
 <thead><tr><th>#</th><th>Nom et prénoms</th><th>IM</th><th>Localité</th>@foreach($b['acts'] as $a)<th class="text-center" title="{{ $a->name }}">{{ $a->code }}<br><small>{{ $fmt($a->amount) }}</small></th>@endforeach<th>Nb</th><th>Montant</th><th>Total</th><th>Écart</th><th>Statut</th></tr></thead>
 @foreach($b['rows'] as $i=>$r)@php $ag=$r['agent']; @endphp
 <tr class="{{ $ag->available?'':'text-muted' }}"><td>{{ $i+1 }}</td><td>{{ $ag->name }}</td><td>{{ $ag->im }}</td><td>{{ $ag->locality }}</td>
  @foreach($b['acts'] as $a)<td class="text-center">@if(in_array($a->id,$r['ids']))<span class="dot"></span>@endif</td>@endforeach
  <td class="text-center">{{ $r['count'] }}</td><td class="text-end">{{ $fmt($r['amount']) }}</td><td class="text-end fw-bold">{{ $fmt($r['total']) }}</td>
  <td class="text-end">{{ $r['dev']===null?'':sprintf('%+.0f %%',$r['dev']*100) }}</td><td class="st-{{ $r['status'] }}">{{ $r['status'] }}</td></tr>
 @endforeach
</table></div>
@endsection

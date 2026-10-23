@extends('layouts.app')
@section('title', $issue['title'])
@section('content')
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
 <a class="btn btn-outline-secondary btn-sm" href="{{ route('dashboard') }}"><i class="bi bi-arrow-left"></i> Tableau de bord</a>
 <h5 class="mb-0 ms-2">{{ $issue['title'] }}</h5>
 <span class="badge text-bg-{{ count($issue['rows']) ? 'danger' : 'success' }}">{{ count($issue['rows']) }}</span>
</div>

<div class="alert alert-info d-flex gap-2"><i class="bi bi-info-circle-fill"></i><div>{{ $issue['help'] }}</div></div>

@if(count($issue['rows']) === 0)
 <div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> Plus aucun problème de ce type : tout est résolu.
  <a href="{{ route('dashboard') }}" class="alert-link">Retour au tableau de bord</a></div>
@else
 <div class="card">
  <div class="table-responsive">
   <table class="table table-hover align-middle mb-0">
    <thead class="table-dark"><tr>@foreach($issue['headers'] as $h)<th>{{ $h }}</th>@endforeach<th class="text-end">Pour résoudre</th></tr></thead>
    @foreach($issue['rows'] as $row)
     <tr>
      @foreach($row['cells'] as $i => $c)<td @if($i === 0) class="fw-bold" @endif>{{ $c }}</td>@endforeach
      <td class="text-end text-nowrap">
       @foreach($row['actions'] as $a)<a class="btn btn-sm {{ $a['class'] }}" href="{{ $a['url'] }}">{{ $a['label'] }}</a> @endforeach
      </td>
     </tr>
    @endforeach
   </table>
  </div>
 </div>
 <div class="mt-3 d-flex gap-2">
  <form method="post" action="{{ route('distribution.run') }}">@csrf
   <button class="btn btn-primary"><i class="bi bi-arrow-repeat"></i> Relancer la répartition après correction</button></form>
  <a class="btn btn-outline-secondary" href="{{ route('dashboard') }}">Retour au tableau de bord</a>
 </div>
@endif
@endsection

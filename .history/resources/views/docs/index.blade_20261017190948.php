@extends('layouts.app')
@section('title','Documents')
@section('content')
<h4>Documents à générer</h4>
<p class="text-muted small">Pour chaque activité : <b>décision de désignation</b> (visas, liste alphabétique paginée, nombre en lettres, Articles 2 et 3), <b>feuille de présence</b> (paysage, une colonne par jour) et <b>état de vacations</b> (jours × taux, total en lettres, émargement). Formats : PDF, Word, Excel.</p>
<table class="table table-bordered bg-white align-middle"><thead class="table-dark"><tr><th>Activité</th><th>Agents</th>
 @foreach(\App\Services\DocumentExporter::TITLES as $t)<th class="text-center">{{ $t }}</th>@endforeach</tr></thead>
@foreach($acts as $a)<tr class="{{ $a->active?'':'text-muted' }}"><td><b>{{ $a->code }}</b><br><small>{{ $a->name }} — {{ $a->days }} j × {{ number_format($a->rate,0,',',' ') }} Ar</small></td><td>{{ $a->assignments_count }}</td>
 @foreach(array_keys(\App\Services\DocumentExporter::TITLES) as $t)<td class="text-center text-nowrap">
  @if($a->assignments_count)
   <a class="btn btn-sm btn-outline-danger" href="{{ route('documents.export',[$a,$t,'pdf']) }}">PDF</a>
   <a class="btn btn-sm btn-outline-primary" href="{{ route('documents.export',[$a,$t,'docx']) }}">Word</a>
   <a class="btn btn-sm btn-outline-success" href="{{ route('documents.export',[$a,$t,'xlsx']) }}">Excel</a>
  @else <span class="text-muted small">aucun agent</span>@endif</td>@endforeach</tr>@endforeach
</table>
<a class="btn btn-outline-secondary" href="{{ route('distribution.recap','xlsx') }}">Récapitulatif agents × activités (Excel)</a>
<a class="btn btn-outline-secondary" href="{{ route('distribution.recap','pdf') }}">Récapitulatif (PDF)</a>
@endsection

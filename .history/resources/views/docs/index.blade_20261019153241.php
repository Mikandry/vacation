@extends('layouts.app')
@section('title','Documents')
@section('content')
<h4>Documents à générer</h4>
<p class="text-muted small">Pour chaque activité : <b>décision</b>, <b>note de service</b> (modèle de la direction, avec logo), <b>feuille de présence</b> (paysage, une colonne par jour) et <b>état de vacations</b>. Formats : PDF, Word, Excel (pas d’Excel pour la note de service).</p>
<table class="table table-bordered bg-white align-middle"><thead class="table-dark"><tr><th>Activité</th><th>Agents</th>
 @foreach(\App\Services\DocumentExporter::TITLES as $t)<th class="text-center">{{ $t }}</th>@endforeach</tr></thead>
@foreach($acts as $a)<tr class="{{ $a->active?'':'text-muted' }}">
 <td><b>{{ $a->code }}</b><br><small>{{ $a->name }} — {{ $a->days }} j × {{ number_format($a->rate,0,',',' ') }} Ar</small>
  <br><a class="small" href="{{ route('choix.show',$a) }}">✎ choisir les agents</a></td>
 <td>{{ $a->assignments_count }}</td>
 @foreach(array_keys(\App\Services\DocumentExporter::TITLES) as $t)
 <td class="text-center text-nowrap">
  @if($a->assignments_count)
   <a class="btn btn-sm btn-outline-danger" href="{{ route('documents.export',[$a,$t,'pdf']) }}">PDF</a>
   <a class="btn btn-sm btn-outline-primary" href="{{ route('documents.export',[$a,$t,'docx']) }}">Word</a>
   @if($t !== 'note')<a class="btn btn-sm btn-outline-success" href="{{ route('documents.export',[$a,$t,'xlsx']) }}">Excel</a>@endif
  @else <span class="text-muted small">aucun agent</span>@endif
 </td>
 @endforeach
</tr>@endforeach
</table>
<a class="btn btn-outline-secondary" href="{{ route('distribution.recap','xlsx') }}">Récapitulatif agents × activités (Excel)</a>
<a class="btn btn-outline-secondary" href="{{ route('distribution.recap','pdf') }}">Récapitulatif (PDF)</a>
@endsection
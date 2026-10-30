@extends('layouts.app')
@section('title','Documents')
@section('content')
<h4>Documents à générer</h4>
<p class="text-muted small">Pour chaque document : <b>👁 Aperçu</b> (vérifier avant de télécharger), puis PDF, Word ou Excel.</p>

<form method="get" class="d-flex align-items-center gap-2 mb-2">
 <label class="small text-muted mb-0">Examen :</label>
 <select name="exam" class="form-select form-select-sm" style="max-width:260px" onchange="this.form.submit()">
  <option value="">Tous les examens</option>
  @foreach($exams as $e)<option value="{{ $e->id }}" @selected((string)$examFilter===(string)$e->id)>{{ $e->code }}</option>@endforeach
  <option value="none" @selected($examFilter==='none')>Sans examen</option>
 </select>
</form>
<div class="input-group input-group-sm mb-3" style="max-width:280px">
 <span class="input-group-text">Taux IRSA (%)</span>
 <input id="irsa" type="number" step="0.1" min="0" max="100" class="form-control" value="{{ $irsa }}">
</div>

<table class="table table-bordered bg-white align-middle">
<thead class="table-dark"><tr><th>Activité</th><th>Agents</th>
 @foreach(\App\Services\DocumentExporter::TITLES as $t)<th class="text-center">{{ $t }}</th>@endforeach</tr></thead>
@foreach($acts as $a)
<tr class="{{ $a->active?'':'text-muted' }}">
 <td><b>{{ $a->code }}</b>@if($a->exam) <span class="badge text-bg-primary">{{ $a->exam->code }}</span>@endif<br><small>{{ $a->name }} — {{ $a->days }} j × {{ number_format($a->rate,0,',',' ') }} Ar</small>
  <br><a class="small" href="{{ route('choix.show',$a) }}">✎ choisir les agents</a></td>
 <td>{{ $a->assignments_count }}</td>
 @foreach(array_keys(\App\Services\DocumentExporter::TITLES) as $t)
 <td class="text-center text-nowrap">
  @if($a->assignments_count)
   @php $u=fn($f)=>route('documents.export',[$a,$t,$f]); $pv=route('documents.preview',[$a,$t]); @endphp
   <a class="btn btn-sm btn-dark mb-1" @if(in_array($t,['decompte','etat'])) data-dec="{{ $pv }}" @endif href="{{ $pv }}">👁 Aperçu</a><br>
   <a class="btn btn-sm btn-outline-danger" @if(in_array($t,['decompte','etat'])) data-dec="{{ $u('pdf') }}" @endif href="{{ $u('pdf') }}">PDF</a>
   <a class="btn btn-sm btn-outline-primary" @if(in_array($t,['decompte','etat'])) data-dec="{{ $u('docx') }}" @endif href="{{ $u('docx') }}">Word</a>
   @if($t!=='note')<a class="btn btn-sm btn-outline-success" @if(in_array($t,['decompte','etat'])) data-dec="{{ $u('xlsx') }}" @endif href="{{ $u('xlsx') }}">Excel</a>@endif
   @if($t==='presence')
    <div class="small text-muted mt-1">sans dates :
     <a href="{{ $u('pdf') }}?dates=0">PDF</a> · <a href="{{ $u('docx') }}?dates=0">Word</a> · <a href="{{ $u('xlsx') }}?dates=0">Excel</a></div>
   @endif
  @else <span class="text-muted small">aucun agent</span>@endif
 </td>
 @endforeach
</tr>
@endforeach
</table>
<a class="btn btn-outline-secondary" href="{{ route('distribution.recap','xlsx') }}">Récapitulatif agents × activités (Excel)</a>
<a class="btn btn-outline-secondary" href="{{ route('distribution.recap','pdf') }}">Récapitulatif (PDF)</a>
@endsection

@push('scripts')
<script>
const irsa=document.getElementById('irsa');
function upd(){
 document.querySelectorAll('a[data-dec]').forEach(a=>{ a.href=a.dataset.dec+'?irsa='+encodeURIComponent(irsa.value); });
}
irsa.addEventListener('input',upd); upd();
</script>
@endpush
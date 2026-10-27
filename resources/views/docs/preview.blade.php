@extends('layouts.app')
@section('title','Aperçu — '.$title)

@php
 $urls = [
  'pdf'  => route('documents.export', ['activity' => $a->id, 'type' => $type, 'format' => 'pdf']),
  'docx' => route('documents.export', ['activity' => $a->id, 'type' => $type, 'format' => 'docx']),
  'xlsx' => route('documents.export', ['activity' => $a->id, 'type' => $type, 'format' => 'xlsx']),
 ];
@endphp

@section('content')
<div class="d-flex flex-wrap gap-2 align-items-center mb-2">
 <h5 class="mb-0 me-auto">Aperçu : {{ $title }} — <span class="text-primary">{{ $a->code }}</span> <small class="text-muted">({{ $a->name }})</small></h5>
 <a class="btn btn-outline-secondary btn-sm" href="{{ route('documents.index') }}">← Documents</a>
</div>

<div class="card card-body mb-2 py-2">
 <div class="row g-2 align-items-center">
  @if(in_array($type, ['decompte','etat']))
  <div class="col-auto">
   <div class="input-group input-group-sm"><span class="input-group-text">Taux IRSA (%)</span>
    <input id="irsa" type="number" step="0.1" min="0" max="100" class="form-control" value="{{ $irsa }}" style="max-width:90px">
   </div>
  </div>
  @endif
  @if($type === 'presence')
  <div class="col-auto">
   <div class="form-check">
    <input class="form-check-input" type="checkbox" id="dates" @checked($showDates)>
    <label class="form-check-label" for="dates">Afficher les dates sur chaque colonne</label>
   </div>
  </div>
  @endif
  <div class="col-auto"><button type="button" class="btn btn-sm btn-primary" id="refresh">↻ Actualiser l’aperçu</button></div>
  <div class="col-auto ms-auto">Télécharger :
   <a class="btn btn-sm btn-danger dl" data-f="pdf" href="#">PDF</a>
   <a class="btn btn-sm btn-primary dl" data-f="docx" href="#">Word</a>
   @if($type !== 'note')<a class="btn btn-sm btn-success dl" data-f="xlsx" href="#">Excel</a>@endif
  </div>
 </div>
</div>
<div class="small text-muted mb-1">L’aperçu montre le PDF. Le Word et l’Excel suivent la même mise en page, avec de légères différences de police.</div>
<iframe id="pv" style="width:100%;height:80vh;border:1px solid #999;background:#fff"></iframe>
@endsection

@push('scripts')
<script>
const base = {!! json_encode($urls) !!};

function opts(){
 const p = new URLSearchParams();
 const i = document.getElementById('irsa');
 if (i && i.value !== '') p.set('irsa', i.value);
 const d = document.getElementById('dates');
 if (d) p.set('dates', d.checked ? 1 : 0);
 return p;
}

function refresh(){
 const p = opts();
 p.set('inline', 1);
 document.getElementById('pv').src = base.pdf + '?' + p.toString();
 const q = opts().toString();
 document.querySelectorAll('.dl').forEach(a => {
  a.href = base[a.dataset.f] + (q ? '?' + q : '');
 });
}

document.getElementById('refresh').addEventListener('click', refresh);
refresh();
</script>
@endpush
@extends('layouts.app')
@section('title','Choix manuel — '.$act->code)
@php $fmt=fn($x)=>number_format((float)$x,0,',',' '); @endphp
@section('content')
<div class="d-flex flex-wrap gap-2 align-items-center mb-3">
 <h4 class="mb-0">Choix manuel — {{ $act->code }} <small class="text-muted fs-6">{{ $act->name }}</small></h4>
 @if($act->manual)<span class="badge bg-warning text-dark">100 % manuel</span>@endif
 <div class="ms-auto d-flex gap-2">
  <select class="form-select form-select-sm" onchange="if(this.value)location=this.value"><option value="">Changer d’activité…</option>
   @foreach($acts as $a)<option value="{{ route('choix.show',$a) }}">{{ $a->code }}</option>@endforeach</select>
  <a class="btn btn-outline-secondary btn-sm" href="{{ route('choix.matrix') }}">← Matrice</a>
  <a class="btn btn-outline-secondary btn-sm" href="{{ route('documents.index') }}">Documents</a>
 </div>
</div>
<div class="row g-2 mb-3">
 <div class="col-6 col-md-3"><div class="kpi"><div class="l">Direction choisis / quota</div><div class="v"><span id="nd">0</span> / {{ $act->quota }}</div></div></div>
 <div class="col-6 col-md-3"><div class="kpi"><div class="l">Agents extérieurs</div><div class="v" id="ne">0</div></div></div>
 <div class="col-6 col-md-3"><div class="kpi"><div class="l">Total / décret</div><div class="v"><span id="nt">0</span> / {{ $act->decree_total }}</div></div></div>
 <div class="col-6 col-md-3"><div class="kpi"><div class="l">Montant total (Ar)</div><div class="v" id="nm">0</div></div></div>
</div>

<form method="post" action="{{ route('choix.external',$act) }}" class="card card-body mb-3">
 @csrf
 <div class="fw-bold mb-2">+ Ajouter un agent extérieur à cette activité</div>
 <div class="row g-2">
  <div class="col-md-5"><input name="name" class="form-control" placeholder="Nom et prénoms" required></div>
  <div class="col-md-2"><input name="im" class="form-control" placeholder="IM"></div>
  <div class="col-md-3"><input name="locality" class="form-control" placeholder="Localité (DAAF, DSI, FIGN…)"></div>
  <div class="col-md-2"><button class="btn btn-success w-100">Ajouter</button></div>
 </div>
</form>

<form method="post" action="{{ route('choix.save',$act) }}">
 @csrf
 <div class="card card-body mb-2">
  <div class="row g-2 align-items-center">
   <div class="col-md-3"><input id="q" class="form-control" placeholder="Rechercher nom, IM, localité…"></div>
   <div class="col-md-2"><select id="t"    <div class="col-md-2"><select id="loc" class="form-select"><option value="">Toutes localités</option>@foreach($localities as $l)<option value="{{ $l }}">{{ $l }}</option>@endforeach</select></div> class="form-select"><option value="">Tous types</option><option>DIRECTION</option><option>EXTERNE</option></select></div>
   <div class="col-auto"><div class="form-check"><input type="checkbox" id="only" class="form-check-input"><label for="only" class="form-check-label">Cochés seulement</label></div></div>
   <div class="col-auto"><button type="button" class="btn btn-outline-primary btn-sm" id="all">Cocher les visibles</button>
    <button type="button" class="btn btn-outline-secondary btn-sm" id="none">Décocher les visibles</button></div>
   <div class="col-auto ms-auto"><div class="form-check"><input type="checkbox" class="form-check-input" id="mm" name="manual_mode" value="1" checked>
    <label for="mm" class="form-check-label">Choix 100 % manuel (ne pas compléter automatiquement jusqu’au quota)</label></div></div>
  </div>
 </div>
 <div class="table-responsive" style="max-height:60vh">
 <table class="table table-sm table-bordered bg-white sticky" id="grid">
  <thead><tr><th style="width:40px"></th><th>Nom et prénoms</th><th>IM</th><th>Localité</th><th>Type</th><th>Fonction</th><th>Autres activités</th><th>Total actuel (Ar)</th></tr></thead>
  @foreach($agents as $ag)
  <tr. data-loc="{{ $ag->locality }}" data-type="{{ $ag->type }}" data-name="{{ \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($ag->name.' '.$ag->im.' '.$ag->locality)) }}" class="{{ $ag->available?'':'text-muted' }}">
   <td class="text-center"><input type="checkbox" class="form-check-input cb" name="agents[]" value="{{ $ag->id }}" @checked(in_array($ag->id,$selected)) @disabled(!$ag->available)></td>
   <td>{{ $ag->name }}@if(!$ag->available) <small>(indisponible)</small>@endif</td><td>{{ $ag->im }}</td><td>{{ $ag->locality }}</td>
   <td><span class="badge text-bg-{{ $ag->type=='DIRECTION'?'primary':'info' }}">{{ $ag->type }}</span></td><td class="small">{{ $ag->function }}</td>
   <td class="small">{{ $info[$ag->id]['codes'] }}</td><td class="text-end">{{ $fmt($info[$ag->id]['total']) }}</td>
  </tr.>@endforeach
 </table></div>
 <div class="mt-3 d-flex gap-2">
  <button class="btn btn-primary">💾 Enregistrer et recalculer</button>
  <a class="btn btn-link" href="{{ route('choix.matrix') }}">Annuler</a>
 </div>
</form>
@if($act->manual)
<form method="post" action="{{ route('choix.auto',$act) }}" class="mt-2">@csrf
 <button class="btn btn-outline-dark btn-sm">Repasser cette activité en automatique (garde les agents cochés)</button></form>
@endif
@endsection
@push('scripts')
<script>
const AMOUNT={{ $act->amount }};
const rows=[...document.querySelectorAll('#grid tbody tr')];
function recount(){
 let d=0,e=0; rows.forEach(r=>{const c=r.querySelector('.cb'); r.classList.toggle('table-primary',c.checked); if(c.checked) r.dataset.type==='DIRECTION'?d++:e++;});
 nd.textContent=d; ne.textContent=e; nt.textContent=d+e; nm.textContent=((d+e)*AMOUNT).toLocaleString('fr-FR');
}
function filt(){
 const qq=q.value.toLowerCase(), tt=t.value, oo=only.checked;
 rows.forEach(r=>r.style.display=(r.dataset.name.includes(qq)&&(!tt||r.dataset.type===tt)&&(!oo||r.querySelector('.cb').checked))?'':'none');
}
[q,t,only].forEach(x=>x.addEventListener('input',filt));
rows.forEach(r=>r.querySelector('.cb').addEventListener('change',recount));
all.onclick=()=>{rows.forEach(r=>{if(r.style.display!=='none'){const c=r.querySelector('.cb'); if(!c.disabled)c.checked=true;}});recount();};
none.onclick=()=>{rows.forEach(r=>{if(r.style.display!=='none')r.querySelector('.cb').checked=false;});recount();};
recount();
</script>
@endpush
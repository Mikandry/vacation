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
  <a class="btn btn-outline-secondary btn-sm" href="{{ route('conflicts.index') }}">Conflits</a>
  <a class="btn btn-outline-secondary btn-sm" href="{{ route('documents.index') }}">Documents</a>
 </div>
</div>

@if(!$act->start_date)
<div class="alert alert-warning small">Cette activité n’a pas de date de début : les conflits de dates ne peuvent pas être détectés.
 <a href="{{ route('activities.edit',$act) }}">Renseigner la date</a>.</div>
@endif

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

@if($act->is_quarantine)
<div class="card card-body mb-3">
 <div class="fw-bold mb-1">Sous-groupes : {{ implode(' / ', $act->subgroup_defs) }}</div>
 <div class="small text-muted mb-2">Cochez les agents, puis choisissez le rôle (sous-groupe) de chacun. Les effectifs prévus se règlent dans « Modifier » l’activité (0 = pas de limite){{ $act->agent_cap > 0 ? ' ; le total ne peut pas dépasser '.$act->agent_cap.' agents' : '' }}.</div>
 @error('sub')<div class="alert alert-danger py-1 small">{{ $message }}</div>@enderror
 <div class="row g-2">
  @foreach($act->subgroup_defs as $k=>$lb)
  <div class="col-6 col-md-3"><div class="kpi"><div class="l">{{ $lb }}</div><div class="v"><span id="sg-{{ $k }}">0</span>{{ $act->subgroup_counts[$k] > 0 ? ' / '.$act->subgroup_counts[$k] : '' }}</div></div></div>
  @endforeach
 </div>
 <div class="small mt-1" id="sg-warn"></div>
</div>
@endif

<form method="post" action="{{ route('choix.save',$act) }}">
 @csrf
 <div class="card card-body mb-2">
  <div class="row g-2 align-items-center">
   <div class="col-md-3"><input id="q" class="form-control" placeholder="Rechercher nom, IM, localité…"></div>
   <div class="col-md-2"><select id="t" class="form-select"><option value="">Tous types</option><option>DIRECTION</option><option>EXTERNE</option></select></div>
   <div class="col-md-2"><select id="loc" class="form-select"><option value="">Toutes localités</option>@foreach($localities as $l)<option value="{{ $l }}">{{ $l }}</option>@endforeach</select></div>
   <div class="col-auto"><div class="form-check"><input type="checkbox" id="only" class="form-check-input"><label for="only" class="form-check-label">Cochés seulement</label></div></div>
   <div class="col-auto">
    <button type="button" class="btn btn-outline-primary btn-sm" id="all">Cocher les visibles</button>
    <button type="button" class="btn btn-outline-secondary btn-sm" id="none">Décocher les visibles</button>
   </div>
   <div class="col-auto ms-auto"><div class="form-check"><input type="checkbox" class="form-check-input" id="mm" name="manual_mode" value="1" checked>
    <label for="mm" class="form-check-label">Choix 100 % manuel (ne pas compléter automatiquement jusqu’au quota)</label></div></div>
  </div>
 </div>

 <div class="table-responsive" style="max-height:60vh">
 <table class="table table-sm table-bordered bg-white sticky" id="grid">
  <thead>
   <tr>
    <th style="width:40px"></th>
    <th>Nom et prénoms</th>
    <th>IM</th>
    <th>Localité</th>
    <th>Type</th>
    @if($act->is_quarantine)<th style="min-width:170px">Sous-groupe</th>@endif
    <th>Fonction</th>
    <th>Autres activités</th>
    <th>⚠ Conflit de dates</th>
    <th>Total actuel (Ar)</th>
   </tr>
  </thead>
  @foreach($agents as $ag)
  <tr data-loc="{{ $ag->locality }}" data-type="{{ $ag->type }}" data-name="{{ \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($ag->name.' '.$ag->im.' '.$ag->locality)) }}" class="{{ $ag->available?'':'text-muted' }}">
   <td class="text-center"><input type="checkbox" class="form-check-input cb" name="agents[]" value="{{ $ag->id }}" @checked(in_array($ag->id,$selected)) @disabled(!$ag->available)></td>
   <td>{{ $ag->name }}@if(!$ag->available) <small>(indisponible)</small>@endif</td>
   <td>{{ $ag->im }}</td>
   <td>{{ $ag->locality }}</td>
   <td><span class="badge text-bg-{{ $ag->type=='DIRECTION'?'primary':'info' }}">{{ $ag->type }}</span></td>
   @if($act->is_quarantine)<td><select name="sub[{{ $ag->id }}]" class="form-select form-select-sm sg"><option value="">—</option>@foreach($act->subgroup_defs as $k=>$lb)<option value="{{ $k }}" @selected((string) ($subMap[$ag->id] ?? '') === (string) $k)>{{ $lb }}</option>@endforeach</select></td>@endif
   <td class="small">{{ $ag->function }}</td>
   <td class="small">{{ $info[$ag->id]['codes'] }}</td>
   <td class="small text-danger fw-bold">{{ $info[$ag->id]['conflict'] }}</td>
   <td class="text-end">{{ $fmt($info[$ag->id]['total']) }}</td>
  </tr>
  @endforeach
 </table>
 </div>

 <div class="mt-3 d-flex gap-2">
  <button class="btn btn-primary">💾 Enregistrer et recalculer</button>
  <a class="btn btn-link" href="{{ route('choix.matrix') }}">Annuler</a>
 </div>
</form>

@if($act->manual)
<form method="post" action="{{ route('choix.auto',$act) }}" class="mt-2">@csrf
 <button class="btn btn-outline-dark btn-sm">Repasser cette activité en automatique (garde les agents cochés)</button>
</form>
@endif
@endsection

@push('scripts')
<script>
const AMOUNT={{ $act->amount }};
const rows=[...document.querySelectorAll('#grid tbody tr')];

function recount(){
 let d=0,e=0;
 rows.forEach(r=>{
  const c=r.querySelector('.cb');
  r.classList.toggle('table-primary',c.checked);
  if(c.checked){ r.dataset.type==='DIRECTION'?d++:e++; }
 });
 nd.textContent=d; ne.textContent=e; nt.textContent=d+e;
 nm.textContent=((d+e)*AMOUNT).toLocaleString('fr-FR');
}

function filt(){
 const qq=q.value.toLowerCase(), tt=t.value, ll=loc.value, oo=only.checked;
 rows.forEach(r=>{
  const ok = r.dataset.name.includes(qq)
          && (!tt || r.dataset.type===tt)
          && (!ll || r.dataset.loc===ll)
          && (!oo || r.querySelector('.cb').checked);
  r.style.display = ok ? '' : 'none';
 });
}

[q,t,loc,only].forEach(x=>x.addEventListener('input',filt));
rows.forEach(r=>r.querySelector('.cb').addEventListener('change',recount));

all.onclick=()=>{
 rows.forEach(r=>{
  if(r.style.display!=='none'){ const c=r.querySelector('.cb'); if(!c.disabled) c.checked=true; }
 });
 recount();
};
none.onclick=()=>{
 rows.forEach(r=>{ if(r.style.display!=='none') r.querySelector('.cb').checked=false; });
 recount();
};
recount();

@if($act->is_quarantine)
const PLAN={!! json_encode((object) $act->subgroup_counts) !!}, CAP={{ $act->agent_cap }};
function recountSg(){
 const c={}; Object.keys(PLAN).forEach(k=>c[k]=0); let none=0;
 rows.forEach(r=>{
  const cb=r.querySelector('.cb'), sel=r.querySelector('.sg');
  sel.disabled=!cb.checked;
  if(cb.checked){ if(sel.value) c[sel.value]++; else none++; }
 });
 let tot=0, over=[];
 for(const k in c){ document.getElementById('sg-'+k).textContent=c[k]; tot+=c[k]; if(PLAN[k]>0 && c[k]>PLAN[k]) over.push(k); }
 const w=document.getElementById('sg-warn'); let m='';
 if(over.length) m+='⚠ Effectif dépassé : '+over.join(', ')+'. ';
 if(CAP>0 && tot>CAP) m+='⚠ Total réparti ('+tot+') > '+CAP+'. ';
 if(none) m+=none+' agent(s) coché(s) sans sous-groupe (« Non réparti » dans les documents).';
 w.className='small mt-1 fw-bold text-danger'; w.textContent=m;
}
rows.forEach(r=>{ r.querySelector('.sg').addEventListener('change',recountSg); r.querySelector('.cb').addEventListener('change',recountSg); });
recountSg();
@endif
</script>
@endpush
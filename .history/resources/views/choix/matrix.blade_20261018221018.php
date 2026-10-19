@extends('layouts.app')
@section('title','Choix manuel')
@section('content')
<div class="d-flex flex-wrap gap-2 align-items-center mb-2">
 <h4 class="mb-0 me-auto">Choix manuel des agents par activité</h4>
 <form method="post" action="{{ route('choix.apply') }}">@csrf <button class="btn btn-primary">✔ Appliquer et recalculer</button></form>
 <a class="btn btn-outline-secondary" href="{{ route('distribution.index') }}">Voir la répartition</a>
</div>
<p class="text-muted small">Cochez les agents de chaque activité (agents extérieurs inclus). Chaque case s’enregistre immédiatement ; cliquez sur « Appliquer » pour recalculer. Cliquez sur le code d’une activité pour la page détaillée (ajout d’un agent extérieur, compteurs, charge de chaque agent).</p>
<div class="row g-2 mb-2">
 <div class="col-md-4"><input id="q" class="form-control" placeholder="Rechercher nom, IM…"></div>
 <div class="col-md-2"><select id="t" div class="row g-2 mb-2 class="form-select"><option value="">Tous types</option><option>DIRECTION</option><option>EXTERNE</option></select></div>
 <div class="col-auto d-flex align-items-center"><div class="form-check"><input type="checkbox" id="only" class="form-check-input"><label for="only" class="form-check-label">Seulement les agents déjà choisis</label></div></div>
</div>
<div class="table-responsive" style="max-height:72vh">
<table class="table table-sm table-bordered bg-white sticky" id="grid">
 <thead><tr><th>Nom et prénoms</th><th>Type</th><th>IM</th>
  @foreach($acts as $a)<th class="text-center"><a class="text-white" href="{{ route('choix.show',$a) }}">{{ $a->code }}</a>
   @if($a->manual)<span class="badge bg-warning text-dark">manuel</span>@endif<br>
   <small class="cnt" data-act="{{ $a->id }}" data-quota="{{ $a->quota }}" data-manual="{{ $a->manual?1:0 }}"></small></th>@endforeach</tr></thead>
 @foreach($agents as $ag)@php $mine=$assigned->get($ag->id,[]); @endphp
 <tr data-name="{{ \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($ag->name.' '.$ag->im)) }}" data-type="{{ $ag->type }}" class="{{ $ag->available?'':'text-muted' }}">
  <td>{{ $ag->name }}</td><td><small>{{ $ag->type }}</small></td><td>{{ $ag->im }}</td>
  @foreach($acts as $a)<td class="text-center"><input type="checkbox" class="form-check-input cb" data-agent="{{ $ag->id }}" data-act="{{ $a->id }}" @checked(in_array($a->id,$mine)) @disabled(!$ag->available)></td>@endforeach
 </tr>@endforeach
</table></div>
@endsection
@push('scripts')
<script>
const rows=[...document.querySelectorAll('#grid tbody tr')];
function counts(){
 document.querySelectorAll('.cnt').forEach(c=>{
  let d=0,e=0; rows.forEach(r=>{const cb=r.querySelector(`.cb[data-act="${c.dataset.act}"]`); if(cb&&cb.checked) r.dataset.type==='DIRECTION'?d++:e++;});
  c.textContent=(c.dataset.manual==='1'?`${d} dir`:`${d}/${c.dataset.quota} dir`)+(e?` + ${e} ext`:'');
 });
}
function filt(){
 const q=document.getElementById('q').value.toLowerCase(), t=document.getElementById('t').value, only=document.getElementById('only').checked;
 rows.forEach(r=>{ const any=[...r.querySelectorAll('.cb')].some(x=>x.checked);
  r.style.display=(r.dataset.name.includes(q)&&(!t||r.dataset.type===t)&&(!only||any))?'':'none'; });
}
['q','t','only'].forEach(i=>document.getElementById(i).addEventListener('input',filt));
document.querySelectorAll('.cb').forEach(cb=>cb.addEventListener('change',async()=>{
 try{
  const res=await fetch('{{ route('choix.toggle') }}',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content},
   body:JSON.stringify({agent_id:cb.dataset.agent,activity_id:cb.dataset.act,on:cb.checked})});
  if(!res.ok) throw new Error();
 }catch(e){ cb.checked=!cb.checked; alert('Échec de l’enregistrement.'); }
 counts();
}));
counts();
</script>
@endpush
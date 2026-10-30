@extends('layouts.app')
@section('title','Activité')
@section('content')
<h4>{{ $a->exists?'Modifier':'Nouvelle' }} activité</h4>
<form method="post" action="{{ $a->exists?route('activities.update',$a):route('activities.store') }}" class="card card-body" style="max-width:900px">
 @csrf @if($a->exists) @method('PUT') @endif
 <div class="row g-3">
  <div class="col-md-4"><label class="form-label">Code court</label><input name="code" class="form-control" value="{{ old('code',$a->code) }}" required></div>
  <div class="col-md-8"><label class="form-label">Nom de l’activité</label><input name="name" class="form-control" value="{{ old('name',$a->name) }}" required></div>
  <div class="col-12"><label class="form-label">Intitulé repris dans la décision (« … pour les travaux de … »)</label><textarea name="title" rows="2" class="form-control">{{ old('title',$a->title) }}</textarea></div>
  <div class="col-md-2"><label class="form-label">Jours</label><input type="number" name="days" class="form-control" value="{{ old('days',$a->days) }}" min="1" max="31" required></div>
  <div class="col-md-3"><label class="form-label">Taux / jour (Ar)</label><input type="number" name="rate" class="form-control" value="{{ old('rate',$a->rate) }}" required></div>
  <div class="col-md-3"><label class="form-label">Quota direction</label><input type="number" name="quota" class="form-control" value="{{ old('quota',$a->quota) }}" required></div>
  <div class="col-md-4"><label class="form-label">Total agents prévu par le décret</label><input type="number" name="decree_total" class="form-control" value="{{ old('decree_total',$a->decree_total) }}"></div>
  <div class="col-md-4"><label class="form-label">Date de début (présence)</label><input type="date" name="start_date" class="form-control" value="{{ old('start_date',optional($a->start_date)->format('Y-m-d')) }}"></div>
  <div class="col-md-3"><label class="form-label">Ordre de traitement</label><input type="number" name="position" class="form-control" value="{{ old('position',$a->position) }}"></div>
  <div class="col-md-3 d-flex align-items-end"><div class="form-check"><input type="checkbox" class="form-check-input" name="active" value="1" id="act" @checked(old('active',$a->active))><label for="act" class="form-check-label">Activité active</label></div></div>

  <div class="col-md-6"><label class="form-label">Examen</label>
   <select name="exam_id" class="form-select">
    <option value="">— aucun —</option>
    @foreach($exams as $e)<option value="{{ $e->id }}" @selected((string) old('exam_id',$a->exam_id)===(string) $e->id)>{{ $e->code }} — {{ $e->name }}</option>@endforeach
   </select>
   <div class="form-text">Pour distinguer « Quarantaine BEPC » de « Quarantaine CEPE ». Les examens se gèrent dans le menu « Examens ». Dans l’intitulé, <code>{examen}</code> est remplacé par le nom complet de l’examen.</div></div>

 @php
  $sgRows = [];
  if (old('sg_name') !== null) {
    foreach ((array) old('sg_name') as $i => $nm) $sgRows[] = ['id' => old('sg_id.'.$i), 'name' => $nm, 'planned' => old('sg_planned.'.$i)];
  } else {
    foreach ($a->exists ? $a->groups : [] as $g) $sgRows[] = ['id' => $g->id, 'name' => $g->name, 'planned' => $g->planned];
  }
 @endphp
 <div class="col-12">
  <div class="card card-body bg-light">
   <div class="fw-bold mb-1">Sous-groupes de l’activité (facultatif)</div>
   <div class="small text-muted mb-2">Si l’activité comporte des rôles (ex. Superviseur, Agent de sécurité), ajoutez-les ici : la décision, la note, la présence, l’état et le décompte se divisent alors automatiquement par sous-groupe, dans l’ordre ci-dessous. Sans sous-groupe, rien ne change. L’effectif prévu est facultatif (0 = pas de limite) ; leur somme ne peut pas dépasser le nombre total d’agents de l’activité. Le rôle de chaque agent se choisit dans « Choisir les agents ».</div>
   <div class="mb-2 small">Modèles :
    @foreach(\App\Models\Activity::TEMPLATES as $tn=>$names)<button type="button" class="btn btn-sm btn-outline-secondary tpl" data-names="{{ json_encode($names, JSON_UNESCAPED_UNICODE) }}">{{ $tn }}</button> @endforeach
   </div>
   <div id="sglist">
    @foreach($sgRows as $row)
    <div class="input-group input-group-sm mb-1 sgrow">
     <input type="hidden" name="sg_id[]" value="{{ $row['id'] }}">
     <input name="sg_name[]" class="form-control" placeholder="Nom du sous-groupe" value="{{ $row['name'] }}">
     <input type="number" min="0" name="sg_planned[]" class="form-control sgp" style="max-width:120px" placeholder="effectif" value="{{ $row['planned'] }}">
     <button type="button" class="btn btn-outline-secondary sgup" title="Monter">↑</button>
     <button type="button" class="btn btn-outline-secondary sgdown" title="Descendre">↓</button>
     <button type="button" class="btn btn-outline-danger sgdel" title="Supprimer">✕</button>
    </div>
    @endforeach
   </div>
   <div><button type="button" class="btn btn-sm btn-outline-primary" id="sgadd">+ Ajouter un sous-groupe</button></div>
   <div class="mt-2 small" id="subinfo"></div>
   @error('sg')<div class="text-danger small fw-bold">{{ $message }}</div>@enderror
  </div>
 </div>
 </div>
 <div class="mt-3"><button class="btn btn-primary">Enregistrer</button> <a class="btn btn-link" href="{{ route('activities.index') }}">Annuler</a></div>
</form>
@endsection
@push('scripts')
<script>
const f=document.querySelector('form.card'), list=document.getElementById('sglist'), info=document.getElementById('subinfo');
function cap(){ const d=parseInt(f.elements['decree_total'].value)||0; return d>0?d:(parseInt(f.elements['quota'].value)||0); }
function esc(t){ return String(t??'').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;'); }
function rowHtml(id,name,planned){
 return '<div class="input-group input-group-sm mb-1 sgrow"><input type="hidden" name="sg_id[]" value="'+esc(id)+'">'
  +'<input name="sg_name[]" class="form-control" placeholder="Nom du sous-groupe" value="'+esc(name)+'">'
  +'<input type="number" min="0" name="sg_planned[]" class="form-control sgp" style="max-width:120px" placeholder="effectif" value="'+esc(planned)+'">'
  +'<button type="button" class="btn btn-outline-secondary sgup" title="Monter">↑</button><button type="button" class="btn btn-outline-secondary sgdown" title="Descendre">↓</button>'
  +'<button type="button" class="btn btn-outline-danger sgdel" title="Supprimer">✕</button></div>';
}
function upd(){
 const rows=[...list.querySelectorAll('.sgrow')];
 const sum=rows.reduce((a,r)=>a+(parseInt(r.querySelector('.sgp').value)||0),0), c=cap();
 if(!rows.length){ info.textContent=''; return; }
 info.className='mt-2 small fw-bold '+((c>0&&sum>c)?'text-danger':(sum===0?'text-muted':(sum===c?'text-success':'text-warning')));
 info.textContent=sum===0?'Aucune limite d’effectif : vous choisirez le rôle de chaque agent dans « Choisir les agents ».':'Total prévu : '+sum+(c>0?' / '+c:'')+((c>0&&sum>c)?' — dépasse le nombre d’agents !':(c>0&&sum<c?' — il reste '+(c-sum)+' à répartir':''));
}
document.getElementById('sgadd').addEventListener('click',()=>{ list.insertAdjacentHTML('beforeend',rowHtml('','','')); upd(); list.lastElementChild.querySelector('[name="sg_name[]"]').focus(); });
list.addEventListener('click',e=>{
 const row=e.target.closest('.sgrow'); if(!row) return;
 if(e.target.classList.contains('sgdel')){ if(!row.querySelector('[name="sg_id[]"]').value || confirm('Supprimer ce sous-groupe ? Les agents qui y sont affectés deviendront « non répartis ».')) row.remove(); }
 if(e.target.classList.contains('sgup') && row.previousElementSibling) row.parentNode.insertBefore(row,row.previousElementSibling);
 if(e.target.classList.contains('sgdown') && row.nextElementSibling) row.parentNode.insertBefore(row.nextElementSibling,row);
 upd();
});
list.addEventListener('input',upd);
document.querySelectorAll('.tpl').forEach(b=>b.addEventListener('click',()=>{
 if(list.children.length && !confirm('Remplacer la liste actuelle par ce modèle ?')) return;
 list.innerHTML=JSON.parse(b.dataset.names).map(n=>rowHtml('',n,'')).join(''); upd();
}));
['decree_total','quota'].forEach(n=>f.elements[n].addEventListener('input',upd));
f.addEventListener('submit',e=>{ const sum=[...list.querySelectorAll('.sgp')].reduce((a,i)=>a+(parseInt(i.value)||0),0); if(cap()>0&&sum>cap()){e.preventDefault(); alert('La somme des sous-groupes dépasse le nombre total d’agents.');} });
upd();
</script>
@endpush

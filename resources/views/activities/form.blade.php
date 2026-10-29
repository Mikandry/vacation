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

 <div class="col-md-6"><label class="form-label">Sous-groupes de l’activité</label>
  <select name="group_type" id="gtype" class="form-select">
   <option value="">Automatique (d’après le nom : quarantaine, dispatching, correction…)</option>
   <option value="NONE" @selected(old('group_type',$a->group_type)==='NONE')>Aucun sous-groupe</option>
   @foreach(\App\Models\Activity::KIND_LABELS as $k=>$lb)<option value="{{ $k }}" @selected(old('group_type',$a->group_type)===$k)>{{ $lb }}</option>@endforeach
  </select></div>

 <div class="col-12" id="subbox" style="display:none">
  <div class="card card-body bg-light">
   <div class="fw-bold mb-1">Répartition des agents en sous-groupes</div>
   <div class="small text-muted mb-2">Effectif prévu par sous-groupe (0 = pas de limite : vous choisissez le rôle de chacun dans « Choisir les agents »). La somme ne peut pas dépasser le nombre total d’agents de l’activité (« Total agents prévu par le décret », sinon « Quota direction »).</div>
   @foreach(\App\Models\Activity::KINDS as $kind=>$defs)
   <div class="row g-2 kindrow" data-kind="{{ $kind }}" style="display:none">
    @foreach($defs as $k=>$label)
    <div class="col-6 col-md-3"><label class="form-label">{{ $label }}</label><input type="number" min="0" name="sub[{{ $k }}]" class="form-control sub" value="{{ old('sub.'.$k, ($a->subgroups[$k] ?? 0)) }}"></div>
    @endforeach
   </div>
   @endforeach
   <div class="mt-2 small" id="subinfo"></div>
   @error('sub')<div class="text-danger small fw-bold">{{ $message }}</div>@enderror
  </div>
 </div>
 </div>
 <div class="mt-3"><button class="btn btn-primary">Enregistrer</button> <a class="btn btn-link" href="{{ route('activities.index') }}">Annuler</a></div>
</form>
@endsection
@push('scripts')
<script>
const f=document.querySelector('form.card'), box=document.getElementById('subbox'), info=document.getElementById('subinfo'), gt=document.getElementById('gtype');
const norm=t=>t.normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase().trim();
function cap(){ const d=parseInt(f.elements['decree_total'].value)||0; return d>0?d:(parseInt(f.elements['quota'].value)||0); }
function kind(){
 if(gt.value==='NONE') return null;
 if(gt.value) return gt.value;
 const n=norm(f.elements['name'].value), c=norm(f.elements['code'].value);
 if((n+' '+c).includes('quarantaine')) return 'QUARANTAINE';
 if((n+' '+c).includes('dispatching')) return 'DISPATCHING';
 if(n.startsWith('correction')||c.startsWith('correction')) return 'CORRECTION';
 return null;
}
function inputs(k){ return [...document.querySelectorAll('.kindrow[data-kind="'+k+'"] .sub')]; }
function upd(){
 const k=kind();
 box.style.display=k?'':'none';
 document.querySelectorAll('.kindrow').forEach(r=>r.style.display=(r.dataset.kind===k)?'':'none');
 if(!k) return;
 const sum=inputs(k).reduce((a,i)=>a+(parseInt(i.value)||0),0), c=cap();
 info.className='mt-2 small fw-bold '+((c>0&&sum>c)?'text-danger':(sum===0?'text-muted':(sum===c?'text-success':'text-warning')));
 info.textContent=sum===0?'Aucune limite : vous choisirez le rôle de chaque agent dans « Choisir les agents ».':'Total réparti : '+sum+' / '+c+((c>0&&sum>c)?' — dépasse le nombre d’agents !':(sum===c?' — OK':' — il reste '+(c-sum)+' à répartir'));
}
['code','name','decree_total','quota'].forEach(n=>f.elements[n].addEventListener('input',upd));
gt.addEventListener('change',upd);
document.querySelectorAll('.sub').forEach(i=>i.addEventListener('input',upd));
f.addEventListener('submit',e=>{ const k=kind(); if(k){ const sum=inputs(k).reduce((a,i)=>a+(parseInt(i.value)||0),0); if(cap()>0&&sum>cap()){e.preventDefault(); alert('La somme des sous-groupes dépasse le nombre total d’agents.');} } });
upd();
</script>
@endpush

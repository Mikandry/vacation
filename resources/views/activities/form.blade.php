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
 </div>
 <div class="mt-3"><button class="btn btn-primary">Enregistrer</button> <a class="btn btn-link" href="{{ route('activities.index') }}">Annuler</a></div>
</form>
@endsection

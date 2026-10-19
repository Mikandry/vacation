@extends('layouts.app')
@section('title','Agent')
@section('content')
<h4>{{ $agent->exists?'Modifier':'Nouvel' }} agent</h4>
<form method="post" action="{{ $agent->exists?route('agents.update',$agent):route('agents.store') }}" class="card card-body" style="max-width:950px">
 @csrf @if($agent->exists) @method('PUT') @endif
 <div class="row g-3">
  <div class="col-md-8"><label class="form-label">Nom et prénoms</label><input name="name" class="form-control" value="{{ old('name',$agent->name) }}" required></div>
  <div class="col-md-4"><label class="form-label">IM</label><input name="im" class="form-control" value="{{ old('im',$agent->im) }}"></div>
  <div class="col-md-4"><label class="form-label">Localité de service</label><input name="locality" class="form-control" value="{{ old('locality',$agent->locality) }}" placeholder="SDC/DEXAMC, DAAF, DSI…"></div>
  <div class="col-md-3"><label class="form-label">Type</label><select name="type" class="form-select">@foreach(['DIRECTION'=>'DIRECTION (réparti automatiquement)','EXTERNE'=>'EXTERNE (exception, ajouté en plus)'] as $k=>$v)<option value="{{ $k }}" @selected(old('type',$agent->type)==$k)>{{ $v }}</option>@endforeach</select></div>
  <div class="col-md-3 d-flex align-items-end"><div class="form-check"><input type="checkbox" class="form-check-input" id="av" name="available" value="1" @checked(old('available',$agent->available))><label class="form-check-label" for="av">Disponible</label></div></div>
  <div class="col-md-4"><label class="form-label">Corps</label><input name="corps" class="form-control" value="{{ old('corps',$agent->corps) }}"></div>
  <div class="col-md-4"><label class="form-label">Grade</label><input name="grade" class="form-control" value="{{ old('grade',$agent->grade) }}"></div>
  <div class="col-md-2"><label class="form-label">Indice</label><input name="indice" class="form-control" value="{{ old('indice',$agent->indice) }}"></div>
  <div class="col-md-6"><label class="form-label">Fonction / observation</label><input name="function" class="form-control" value="{{ old('function',$agent->function) }}"></div>
  <div class="col-md-3"><label class="form-label">Historique (Ar déjà perçus)</label><input type="number" name="history_amount" class="form-control" value="{{ old('history_amount',$agent->history_amount) }}"></div>
    <div class="col-md-6"><label class="form-label">N° de la note de service (propre à cette activité)</label><input name="note_number" class="form-control" value="{{ old('note_number',$a->note_number) }}" placeholder="N°2026/549MEN/SG/DGES/DEXAMC"></div>
  <div class="col-md-3"><label class="form-label">Ordre de tirage (départage)</label><input type="number" name="draw_order" class="form-control" value="{{ old('draw_order',$agent->draw_order) }}"></div>
  <div class="col-12"><label class="form-label">Remarques</label><textarea name="notes" rows="2" class="form-control">{{ old('notes',$agent->notes) }}</textarea></div>
 </div>
 <div class="mt-3"><button class="btn btn-primary">Enregistrer</button> <a class="btn btn-link" href="{{ route('agents.index') }}">Annuler</a></div>
</form>
@endsection

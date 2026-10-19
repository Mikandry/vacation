@extends('layouts.app')
@section('title','Paramètres')
@php
 $groups=[
  'Décision'=>[['direction','Direction'],['session','Session (année)'],['ministry','En-tête (ministère)'],['decision_number','N° de la décision'],['place','Lieu de signature'],['decision_date','Date de la décision (texte libre, ex. 15 octobre 2026)'],['authority','Autorité (ligne après l’en-tête)'],['signatory','Titre du signataire'],['ref_arrete','Référence de l’arrêté fixant les taux (Article 2)']],
  'Ligne budgétaire (Article 2)'=>[['budget_mission','Mission'],['budget_indicator','Indicateur d’objectif'],['budget_section','Section'],['budget_account','Compte'],['budget_ordsec','Code ORDSEC']],
 ];
@endphp
@section('content')
<h4>Paramètres</h4>
<form method="post" action="{{ route('settings.update') }}" class="row g-3">
 @csrf
 @foreach($groups as $title=>$fields)
  <div class="col-lg-6"><div class="card"><div class="card-header fw-bold">{{ $title }}</div><div class="card-body row g-2">
   @foreach($fields as [$k,$l])<div class="col-12"><label class="form-label mb-0 small">{{ $l }}</label><input name="{{ $k }}" class="form-control" value="{{ old($k,$s[$k]) }}"></div>@endforeach
  </div></div></div>
 @endforeach
 <div class="col-lg-6"><div class="card"><div class="card-header fw-bold">Équilibre et répartition</div><div class="card-body row g-2">
  <div class="col-md-6"><label class="form-label mb-0 small">Répartition automatique</label><select name="auto" class="form-select">@foreach(['OUI','NON'] as $v)<option @selected($s['auto']==$v)>{{ $v }}</option>@endforeach</select></div>
  <div class="col-md-6"><label class="form-label mb-0 small">Tenir compte de l’historique</label><select name="use_history" class="form-select">@foreach(['OUI','NON'] as $v)<option @selected($s['use_history']==$v)>{{ $v }}</option>@endforeach</select></div>
  <div class="col-md-4"><label class="form-label mb-0 small">Tolérance ± (% de la moyenne)</label><input type="number" step="0.1" name="tolerance" class="form-control" value="{{ old('tolerance',$s['tolerance']) }}"></div>
  <div class="col-md-4"><label class="form-label mb-0 small">Seuil « ÉQUILIBRÉ » (% agents)</label><input type="number" name="threshold_ok" class="form-control" value="{{ old('threshold_ok',$s['threshold_ok']) }}"></div>
  <div class="col-md-4"><label class="form-label mb-0 small">Seuil « ACCEPTABLE » (% agents)</label><input type="number" name="threshold_acc" class="form-control" value="{{ old('threshold_acc',$s['threshold_acc']) }}"></div>
  <div class="col-md-6"><label class="form-label mb-0 small">Pas des tranches de montant (Ar)</label><input type="number" name="band_step" class="form-control" value="{{ old('band_step',$s['band_step']) }}"></div>
  <div class="col-12"><label class="form-label mb-0 small">Services de la direction (une ligne : <code>CODE|Nom complet</code>, CODE = localité de service des agents)</label><textarea name="services" rows="5" class="form-control">{{ old('services',$s['services']) }}</textarea></div>
 </div></div></div>
 <div class="col-12"><button class="btn btn-primary">Enregistrer</button></div>
</form>
@endsection

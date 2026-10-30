@extends('layouts.app')
@section('title','Examens')
@section('content')
<h4>Examens</h4>
<p class="text-muted small">Un examen regroupe des activités (BEPC, CEPE, CAP/EP…). Le code de l’examen distingue deux activités du même nom (« Quarantaine BEPC » / « Quarantaine CEPE »). Les champs facultatifs <b>remplacent</b> les valeurs des Paramètres pour les documents de cet examen ; laissés vides, ce sont les Paramètres qui servent. <b>L’équilibre des vacations reste calculé sur tous les examens ensemble.</b></p>
@if($errors->any())<div class="alert alert-danger py-1 small">{{ $errors->first() }}</div>@endif

<div class="card card-body mb-3">
 <div class="fw-bold mb-2">+ Ajouter un examen</div>
 <form method="post" action="{{ route('exams.store') }}" class="row g-2">@csrf
  <div class="col-md-2"><input name="code" class="form-control form-control-sm" placeholder="Code (ex. BEPC)" required></div>
  <div class="col-md-5"><input name="name" class="form-control form-control-sm" placeholder="Nom complet de l’examen" required></div>
  <div class="col-md-2"><input name="session" class="form-control form-control-sm" placeholder="Session (facultatif)"></div>
  <div class="col-md-3"><input name="decision_number" class="form-control form-control-sm" placeholder="N° décision (facultatif)"></div>
  <div class="col-12"><textarea name="ref_arrete" rows="1" class="form-control form-control-sm" placeholder="Référence de l’arrêté fixant les taux (facultatif)"></textarea></div>
  <div class="col-12"><button class="btn btn-sm btn-primary">Ajouter</button></div>
 </form>
</div>

@forelse($exams as $e)
<div class="card card-body mb-2">
 <form method="post" action="{{ route('exams.update',$e) }}" class="row g-2">@csrf @method('PUT')
  <div class="col-md-2"><label class="form-label small mb-0">Code</label><input name="code" class="form-control form-control-sm fw-bold" value="{{ $e->code }}" required></div>
  <div class="col-md-5"><label class="form-label small mb-0">Nom complet</label><input name="name" class="form-control form-control-sm" value="{{ $e->name }}" required></div>
  <div class="col-md-2"><label class="form-label small mb-0">Session</label><input name="session" class="form-control form-control-sm" value="{{ $e->session }}" placeholder="Paramètres"></div>
  <div class="col-md-3"><label class="form-label small mb-0">N° décision</label><input name="decision_number" class="form-control form-control-sm" value="{{ $e->decision_number }}" placeholder="Paramètres"></div>
  <div class="col-12"><label class="form-label small mb-0">Référence de l’arrêté fixant les taux</label><textarea name="ref_arrete" rows="1" class="form-control form-control-sm" placeholder="Paramètres">{{ $e->ref_arrete }}</textarea></div>
  <div class="col-md-2"><label class="form-label small mb-0">Ordre</label><input type="number" min="0" name="position" class="form-control form-control-sm" value="{{ $e->position }}"></div>
  <div class="col-md-10 d-flex align-items-end gap-2">
   <button class="btn btn-sm btn-primary">Enregistrer</button>
   <span class="small text-muted">{{ $e->activities_count }} activité(s)</span>
   <a class="small" href="{{ route('activities.index',['exam'=>$e->id]) }}">voir</a>
   <button type="submit" form="del{{ $e->id }}" class="btn btn-sm btn-outline-danger ms-auto" onclick="return confirm('Supprimer cet examen ? Ses activités sont conservées, sans examen.')">Supprimer</button>
  </div>
 </form>
 <form id="del{{ $e->id }}" method="post" action="{{ route('exams.destroy',$e) }}">@csrf @method('DELETE')</form>
</div>
@empty
<p class="text-muted">Aucun examen pour le moment.</p>
@endforelse
@endsection

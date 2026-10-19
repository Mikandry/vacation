@extends('layouts.app')
@section('title','Import')
@section('content')
<h4>Importer des agents depuis Excel</h4>
<form method="post" action="{{ route('agents.import') }}" enctype="multipart/form-data" class="card card-body" style="max-width:700px">
 @csrf
 <p>Formats acceptés : <b>.xlsx / .csv</b>. Idéal : la feuille <b>AGENTS</b> du classeur Excel fourni (ou un export de cette application).<br>
 Colonnes reconnues par leur en-tête : <code>NOM ET PRENOMS, IM, LOCALITE, TYPE, DISPONIBLE, CORPS, GRADE, INDICE, FONCTION, HISTORIQUE, ORDRE</code>
 et une colonne par <b>code d’activité</b> contenant <code>F</code> (forcer) ou <code>X</code> (exclure).</p>
 <input type="file" name="file" class="form-control mb-3" required>
 <div><button class="btn btn-primary">Importer</button> <a class="btn btn-link" href="{{ route('agents.index') }}">Annuler</a></div>
 <p class="small text-muted mt-3 mb-0">Les agents existants (même IM, ou même nom si l’IM est vide) sont mis à jour, les autres créés.</p>
</form>
@endsection

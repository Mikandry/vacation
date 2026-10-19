@extends('layouts.app')
@section('title','Agents')
@section('content')
<div class="d-flex flex-wrap gap-2 align-items-center mb-3">
 <h4 class="mb-0 me-auto">Agents ({{ $total }})</h4>
 <a class="btn btn-primary" href="{{ route('agents.create') }}">+ Ajouter</a>
 <a class="btn btn-outline-primary" href="{{ route('agents.import.form') }}">Importer Excel</a>
 <a class="btn btn-outline-success" href="{{ route('agents.export') }}">Exporter Excel</a>
</div>
<form class="row g-2 mb-3" method="get">
 <div class="col-md-4"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Nom, IM, localité…"></div>
 <div class="col-md-2"><select name="type" class="form-select"><option value="">Tous types
     <div class="col-md-3"><select name="locality" class="form-select"><option value="">Toutes localités</option>@foreach($localities as $l)<option value="{{ $l }}" @selected(request('locality')==$l)>{{ $l }}</option>@endforeach</select></div>
    </option>@foreach(['DIRECTION','EXTERNE'] as $t)<option @selected(request('type')==$t)>{{ $t }}</option>@endforeach</select></div>
 <div class="col-md-2"><select name="dispo" class="form-select"><option value="">Dispo : tous</option><option value="1" @selected(request('dispo')==='1')>Disponibles</option><option value="0" @selected(request('dispo')==='0')>Indisponibles</option></select></div>
 <div class="col-auto"><button class="btn btn-secondary">Filtrer</button></div>
</form>
<table class="table table-sm table-bordered bg-white align-middle"><thead class="table-dark"><tr><th>Nom et prénoms</th><th>IM</th><th>Localité</th><th>Type</th><th>Dispo</th><th>Corps / grade</th><th>Fonction</th><th>Historique (Ar)</th><th></th></tr></thead>
@foreach($agents as $a)<tr class="{{ $a->available?'':'text-muted' }}"><td>{{ $a->name }}</td><td>{{ $a->im }}</td><td>{{ $a->locality }}</td><td><span class="badge text-bg-{{ $a->type=='DIRECTION'?'primary':'info' }}">{{ $a->type }}</span></td><td>{{ $a->available?'oui':'non' }}</td>
 <td class="small">{{ $a->corps }} {{ $a->grade }}</td><td class="small">{{ $a->function }}</td><td class="text-end">{{ number_format($a->history_amount,0,',',' ') }}</td>
 <td class="text-nowrap"><a class="btn btn-sm btn-outline-primary" href="{{ route('agents.edit',$a) }}">Modifier</a>
 <form method="post" action="{{ route('agents.destroy',$a) }}" class="d-inline" onsubmit="return confirm('Supprimer cet agent ?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">✕</button></form></td></tr>@endforeach
</table>
{{ $agents->links('pagination::bootstrap-5') }}
@endsection

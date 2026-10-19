@extends('layouts.app')
@section('title','Activités')
@section('content')
<div class="d-flex mb-3"><h4 class="me-auto">Activités</h4><a class="btn btn-primary" href="{{ route('activities.create') }}">+ Ajouter une activité</a></div>
<p class="text-muted small">« Ordre » = ordre de traitement : mettre les activités les plus lourdes en premier donne un meilleur équilibre. Quota = nombre d’agents de votre direction.</p>
<table class="table table-sm table-bordered bg-white align-middle"><thead class="table-dark"><tr><th>Ordre</th><th>Code</th><th>Activité</th><th>Jours</th><th>Taux/j</th><th>Montant/agent</th><th>Quota direction</th><th>Décret (total)</th><th>Début</th><th>Actif</th><th></th></tr></thead>
@foreach($activities as $a)<tr class="{{ $a->active?'':'text-muted' }}"><td>{{ $a->position }}</td><td class="fw-bold">{{ $a->code }}</td><td>{{ $a->name }}</td><td>{{ $a->days }}</td><td class="text-end">{{ number_format($a->rate,0,',',' ') }}</td><td class="text-end">{{ number_format($a->amount,0,',',' ') }}</td><td>{{ $a->quota }}</td><td>{{ $a->decree_total }}</td><td>{{ optional($a->start_date)->format('d/m/Y') }}</td><td>{{ $a->active?'oui':'non' }}</td>
 <td class="text-nowrap">
    <a class="btn btn-sm btn-outline-primary" href="{{ route('activities.edit',$a) }}">Modifier</a>
 <form method="post" action="{{ route('activities.destroy',$a) }}" class="d-inline" onsubmit="return confirm('Supprimer cette activité ?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Supprimer</button></form></td></tr>@endforeach
</table>
@endsection

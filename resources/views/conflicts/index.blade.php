@extends('layouts.app')
@section('title','Conflits de dates')
@section('content')
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
 <h4 class="mb-0 me-auto">Conflits de dates entre activités</h4>
 <a class="btn btn-outline-secondary btn-sm" href="{{ route('activities.index') }}">Modifier les dates des activités</a>
</div>
<p class="text-muted small">Un agent est en conflit s’il est désigné dans deux activités dont les dates se chevauchent, <b>même d’un seul jour</b>. Pour chaque conflit, choisissez l’activité à garder : l’autre est retirée pour cet agent, puis la répartition est recalculée.</p>

@if($undated->count())
<div class="alert alert-warning small">⚠ Dates de début manquantes pour : <b>{{ $undated->pluck('code')->implode(', ') }}</b>.
 Les chevauchements de ces activités ne peuvent pas être détectés. <a href="{{ route('activities.index') }}">Renseigner les dates</a>.</div>
@endif

<div class="card mb-3"><div class="card-header fw-bold">Calendrier des activités</div>
<table class="table table-sm mb-0 text-center"><thead class="table-light"><tr><th class="text-start">Activité</th><th>Début</th><th>Fin</th><th>Jours</th></tr></thead>
@foreach($acts as $a)<tr><td class="text-start"><b>{{ $a->code }}</b></td><td>{{ optional($a->start_date)->format('d/m/Y') ?? '—' }}</td><td>{{ optional($a->end_date)->format('d/m/Y') ?? '—' }}</td><td>{{ $a->days }}</td></tr>@endforeach
</table></div>

@if(count($conflicts)===0)
 <div class="alert alert-success">✔ Aucun conflit : aucun agent n’est dans deux activités qui se chevauchent.</div>
@else
 <div class="alert alert-danger"><b>{{ count($conflicts) }}</b> conflit(s) à résoudre.</div>
 <div class="table-responsive">
 <table class="table table-sm table-bordered bg-white align-middle">
  <thead class="table-dark"><tr><th>Agent</th><th>Activité A</th><th>Activité B</th><th>Chevauchement</th><th>Votre décision</th></tr></thead>
  @foreach($conflicts as $c)
  <tr>
   <td><b>{{ $c['agent']->name }}</b><br><small class="text-muted">{{ $c['agent']->im }} · {{ $c['agent']->locality }} · {{ $c['agent']->type }}</small></td>
   <td><b>{{ $c['a']->code }}</b> @if($c['forced_a'])<span class="badge text-bg-success">forcé</span>@endif<br><small>{{ $c['a']->start_date->format('d/m') }} → {{ $c['a']->end_date->format('d/m/Y') }}</small></td>
   <td><b>{{ $c['b']->code }}</b> @if($c['forced_b'])<span class="badge text-bg-success">forcé</span>@endif<br><small>{{ $c['b']->start_date->format('d/m') }} → {{ $c['b']->end_date->format('d/m/Y') }}</small></td>
   <td class="text-danger fw-bold">{{ $c['from']->format('d/m') }} → {{ $c['to']->format('d/m') }}<br><small>{{ $c['days'] }} jour(s)</small></td>
   <td class="text-nowrap">
    @foreach([[$c['a'],$c['b']],[$c['b'],$c['a']]] as [$keep,$drop])
    <form method="post" action="{{ route('conflicts.resolve') }}" class="d-inline" onsubmit="return confirm('Garder {{ $keep->code }} et retirer {{ $drop->code }} pour {{ addslashes($c['agent']->name) }} ?')">
     @csrf
     <input type="hidden" name="agent_id" value="{{ $c['agent']->id }}"><input type="hidden" name="keep" value="{{ $keep->id }}"><input type="hidden" name="drop" value="{{ $drop->id }}">
     <button class="btn btn-sm btn-success">Garder {{ $keep->code }}<br><small>retirer {{ $drop->code }}</small></button>
    </form>
    @endforeach
    <form method="post" action="{{ route('conflicts.waive') }}" class="d-inline">
     @csrf
     <input type="hidden" name="agent_id" value="{{ $c['agent']->id }}"><input type="hidden" name="a" value="{{ $c['a']->id }}"><input type="hidden" name="b" value="{{ $c['b']->id }}">
     <button class="btn btn-sm btn-outline-secondary">Garder les deux<br><small>(ignorer)</small></button>
    </form>
   </td>
  </tr>
  @endforeach
 </table></div>
@endif

@if($waivers->count())
<div class="card mt-3"><div class="card-header fw-bold">Conflits ignorés volontairement</div>
<table class="table table-sm mb-0 align-middle"><tbody>
@foreach($waivers as $w)<tr><td>{{ optional($w->agent)->name }}</td><td>{{ $actMap[$w->activity_a_id]->code ?? '?' }} + {{ $actMap[$w->activity_b_id]->code ?? '?' }}</td>
 <td class="text-end"><form method="post" action="{{ route('conflicts.unwaive',$w) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-primary">Rétablir la vérification</button></form></td></tr>@endforeach
</tbody></table></div>
@endif
@endsection
@extends('layouts.app')
@section('title','Exceptions')
@section('content')
<h4>Exceptions par agent et par activité</h4>
<p class="text-muted small"><b>F</b> = forcer la présence (même hors quota/décret — pour les personnes à ajouter « à part ») · <b>X</b> = exclure de l’activité. Enregistrement immédiat. Puis relancez la répartition.</p>
<form class="row g-2 mb-3" method="get">
 <div class="col-md-4"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Nom ou IM"></div>
 <div class="col-md-2"><select name="type" class="form-select"><option value="">Tous types</option>@foreach(['DIRECTION','EXTERNE'] as $t)<option @selected(request('type')==$t)>{{ $t }}</option>@endforeach</select></div>
 <div class="col-auto"><button class="btn btn-secondary">Filtrer</button></div>
</form>
<div class="table-responsive" style="max-height:72vh"><table class="table table-sm table-bordered bg-white sticky">
 <thead><tr><th>Nom et prénoms</th><th>Type</th><th>IM</th>@foreach($acts as $a)<th class="text-center" title="{{ $a->name }}">{{ $a->code }}</th>@endforeach</tr></thead>
 @foreach($agents as $ag)<tr><td>{{ $ag->name }}</td><td><small>{{ $ag->type }}</small></td><td>{{ $ag->im }}</td>
  @foreach($acts as $a)@php $m=optional($ag->exceptions->firstWhere('activity_id',$a->id))->mode; @endphp
  <td class="text-center p-0"><select class="form-select form-select-sm ex border-0 {{ $m=='F'?'bg-success-subtle fw-bold':($m=='X'?'bg-danger-subtle fw-bold':'') }}" data-agent="{{ $ag->id }}" data-act="{{ $a->id }}" style="min-width:64px"><option value="">—</option><option value="F" @selected($m=='F')>F</option><option value="X" @selected($m=='X')>X</option></select></td>@endforeach</tr>@endforeach
</table></div>
{{ $agents->links('pagination::bootstrap-5') }}
<form method="post" action="{{ route('distribution.run') }}" class="mt-3">@csrf <button class="btn btn-primary">⟳ Relancer la répartition</button></form>
@endsection
@push('scripts')
<script>
document.querySelectorAll('.ex').forEach(s=>s.addEventListener('change',async e=>{
 const el=e.target; el.classList.remove('bg-success-subtle','bg-danger-subtle','fw-bold');
 if(el.value==='F')el.classList.add('bg-success-subtle','fw-bold'); if(el.value==='X')el.classList.add('bg-danger-subtle','fw-bold');
 await fetch('{{ route('exceptions.set') }}',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content,'Accept':'application/json'},
  body:JSON.stringify({agent_id:el.dataset.agent,activity_id:el.dataset.act,mode:el.value||null})});
}));
</script>
@endpush

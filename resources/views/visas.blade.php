@extends('layouts.app')
@section('title','Visas')
@section('content')
<h4>Visas / considérants de la décision</h4>
<p class="text-muted small">Modifiez le texte, décochez « Inclure » pour retirer un visa, cochez « Suppr. » pour l’effacer. Ajoutez-en dans les lignes vides en bas. L’ordre est celui de la liste.</p>
<form method="post" action="{{ route('visas.update') }}">
 @csrf
 <table class="table table-sm bg-white align-middle"><thead class="table-dark"><tr><th>#</th><th>Texte</th><th>Inclure</th><th>Suppr.</th></tr></thead>
 @php $rows=$visas->all(); for($i=0;$i<5;$i++) $rows[]=new \App\Models\Visa(['text'=>'','included'=>true]); @endphp
 @foreach($rows as $i=>$v)<tr><td>{{ $i+1 }}</td><td style="width:80%"><textarea name="visas[{{ $i }}][text]" rows="2" class="form-control form-control-sm">{{ $v->text }}</textarea></td>
  <td class="text-center"><input type="checkbox" name="visas[{{ $i }}][included]" value="1" @checked($v->included)></td><td class="text-center"><input type="checkbox" name="visas[{{ $i }}][delete]" value="1"></td></tr>@endforeach
 </table>
 <button class="btn btn-primary">Enregistrer les visas</button>
</form>
@endsection

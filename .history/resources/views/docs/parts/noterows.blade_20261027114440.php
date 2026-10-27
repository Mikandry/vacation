@foreach($rows as $r)
@if($r[0]==='h')<tr><td colspan="4" style="background:#eee;font-weight:bold">{{ $r[1] }}</td></tr>
@elseif($r[0]==='f')<tr><td colspan="4" style="font-weight:bold;font-style:italic">{{ $r[1] }}</td></tr>
@else<tr><td width="6%" style="text-align:right">{{ $r[1] }}</td><td width="51%">{{ $r[2]->name }}</td><td width="14%" class="c">{{ $r[2]->im }}</td><td width="29%" class="c">{{ $r[2]->locality }}</td></tr>
@endif
@endforeach
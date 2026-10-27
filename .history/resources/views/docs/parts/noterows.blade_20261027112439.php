@foreach($rows as $r)
@if($r[0]==='h')<tr><td colspan="4" style="background:#eee;font-weight:bold">{{ $r[1] }}</td></tr>
@elseif($r[0]==='f')<tr><td colspan="4" style="font-weight:bold;font-style:italic">{{ $r[1] }}</td></tr>
@else<tr><td style="text-align:right">{{ $r[1] }}</td><td>{{ $r[2]->name }}</td><td class="c">{{ $r[2]->im }}</td><td class="c">{{ $r[2]->locality }}</td></tr>
@endif
@endforeach

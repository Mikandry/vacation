@foreach($rows as $k=>$it)
<tr style="page-break-inside:avoid"><td width="11%" class="c">{{ $it['g'] }}</td><td width="7%" class="c">{{ $quar ? $it['l'] : ($from + $k + 1) }}</td><td>{{ $it['ag']->name }}</td><td width="13%" class="c">{{ $it['ag']->im }}</td><td width="22%" class="c">{{ $it['ag']->locality }}</td></tr>
@endforeach
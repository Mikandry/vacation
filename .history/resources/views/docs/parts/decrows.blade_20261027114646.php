@foreach($rows as $k=>$ag)
@php $i = $from + $k; @endphp
<tr style="page-break-inside:avoid"><td width="11%" class="c">{{ $ch['offset'] + $i + 1 }}</td><td width="7%" class="c">{{ ($local ? $ch['local'] : 0) + $i + 1 }}</td><td>{{ $ag->name }}</td><td width="13%" class="c">{{ $ag->im }}</td><td width="22%" class="c">{{ $ag->locality }}</td></tr>
@endforeach
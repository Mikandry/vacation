@php
 $days = max(1, (int) $a->days);
 $idw  = max(25, min(60, (int) ($s['presence_id_width'] ?? 40)));
 $wN   = 4;
 $wIm  = round(($idw - $wN) * 0.17, 2);
 $wLoc = round(($idw - $wN) * 0.21, 2);
 $wName = round($idw - $wN - $wIm - $wLoc, 2);
 $dw   = round((100 - $idw) / $days, 2);
@endphp
<!doctype html><html><head><meta charset="utf-8"><style>
@page{margin:22px 24px} body{font-family:'DejaVu Sans',sans-serif;font-size:7.5pt}
h1{font-size:15pt;text-align:center;margin:4px 0} .c{text-align:center}
table{width:100%;border-collapse:collapse;table-layout:fixed} th,td{border:1px solid #000;padding:2px 3px;height:19px;overflow:hidden}
th{background:#dde6f1;text-align:center} thead{display:table-header-group} tr{page-break-inside:avoid} td.n{white-space:nowrap}
</style></head><body>
<table style="border:0"><tr style="border:0"><td style="border:0" width="33%"><b>{{ $s['ministry'] }}</b></td><td style="border:0"><h1>{{ $docs->tpl($s['presence_title'], get_defined_vars()) }}</h1></td><td style="border:0" width="33%"></td></tr></table>
<p class="c"><b>{{ $docs->tpl($s['presence_line1'], get_defined_vars()) }}</b><br>{{ $docs->tpl($s['presence_line2'], get_defined_vars()) }}</p>
<table><colgroup><col style="width:{{ $wN }}%"><col style="width:{{ $wName }}%"><col style="width:{{ $wIm }}%"><col style="width:{{ $wLoc }}%">@for($k=0;$k<$days;$k++)<col style="width:{{ $dw }}%">@endfor</colgroup>
<thead><tr><th>N°</th><th>NOM ET PRENOMS</th><th>IM</th><th>LOCALITE</th>@for($k=0;$k<$days;$k++)<th>{{ $k+1 }}@if($dates)<br><span style="font-size:5pt">{{ $dates[$k] }}</span>@endif</th>@endfor</tr></thead>
@foreach($agents as $i=>$ag)<tr><td class="c">{{ $i+1 }}</td><td class="n">{{ $ag->name }}</td><td class="c">{{ $ag->im }}</td><td class="c">{{ $ag->locality }}</td>@for($k=0;$k<$days;$k++)<td></td>@endfor</tr>@endforeach
</table></body></html>
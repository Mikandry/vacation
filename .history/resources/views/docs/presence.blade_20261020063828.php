@php
 $idw   = max(25, min(60, (int) $s['presence_id_width']));
 $wN    = 3.5;
 $wIm   = round($idw * 0.20, 2);
 $wLoc  = round($idw * 0.27, 2);
 $wName = round($idw - $wN - $wIm - $wLoc, 2);
 $rh    = max(18, (int) $s['presence_row_height']);
 $logo  = ($s['note_show_logo'] ?? 'OUI') === 'OUI' ? \App\Support\Logo::uri() : null;
 $left  = preg_split('/\R/', $s['presence_header_left']);
 $nSeries = count($series);
@endphp
<!doctype html><html><head><meta charset="utf-8"><style>
@page{margin:18px 26px}
body{font-family:'DejaVu Serif',serif;font-size:8pt;color:#000}
.c{text-align:center}
table.lh{width:100%;border-collapse:collapse;margin-bottom:8px} table.lh td{vertical-align:middle;padding:0 4px}
table.p{width:100%;border-collapse:collapse;table-layout:fixed}
table.p th,table.p td{border:1px solid #000;padding:2px 3px;overflow:hidden}
table.p th{font-size:7.5pt;text-align:center;vertical-align:middle}
table.p td{vertical-align:middle}
thead{display:table-header-group} tr{page-break-inside:avoid}
.dt{font-size:7pt;font-weight:bold;line-height:1.1;height:34px}
</style></head><body>
@foreach($series as $si=>$sr)
 <table class="lh"><tr>
  <td width="40%" class="c" style="font-size:7.5pt">@foreach($left as $l){{ $l }}<br>@endforeach</td>
  <td class="c">
   @if($logo)<img src="{{ $logo }}" height="46"><br>@endif
   <b style="font-size:8.5pt">{{ $docs->tpl($s['presence_title'], get_defined_vars()) }}<br>{{ $docs->tpl($s['presence_line1'], get_defined_vars()) }}</b>
   @if(trim($s['presence_line2'])!=='')<br><span style="font-size:8pt">{{ $docs->tpl($s['presence_line2'], get_defined_vars()) }}</span>@endif
   @if($nSeries>1)<br><i style="font-size:7.5pt">Feuille {{ $si+1 }}/{{ $nSeries }} — jours {{ $sr['from'] }} à {{ $sr['to'] }}</i>@endif
  </td></tr></table>

 @php $dw = round((100-$idw)/$sr['cols'],3); @endphp
 <table class="p">
  <colgroup><col style="width:{{ $wN }}%"><col style="width:{{ $wName }}%"><col style="width:{{ $wIm }}%"><col style="width:{{ $wLoc }}%">@for($k=0;$k<$sr['cols'];$k++)<col style="width:{{ $dw }}%">@endfor</colgroup>
  <thead>
   <tr><th rowspan="2">N°</th><th rowspan="2">NOM ET PRENOMS</th><th rowspan="2">IM</th><th rowspan="2">LOCALITE DE SERVICE</th><th colspan="{{ $sr['cols'] }}">EMARGEMENT</th></tr>
   <tr>@for($k=0;$k<$sr['cols'];$k++)<th class="dt">@if($sr['labels'][$k]!==''){{ substr($sr['labels'][$k],0,5) }}<br>{{ substr($sr['labels'][$k],6) }}@endif</th>@endfor</tr>
  </thead>
  @foreach($agents as $i=>$ag)
  <tr style="height:{{ $rh }}pt">
   <td class="c">{{ $i+1 }}</td><td>{{ $ag->name }}</td><td class="c">{{ $docs->fmtIm($ag->im) }}</td><td class="c">{{ $ag->locality }}</td>
   @for($k=0;$k<$sr['cols'];$k++)<td></td>@endfor
  </tr>
  @endforeach
 </table>
 @if($loop->last)<p style="margin-top:10px">{{ $s['place'] }}, le {{ $s['decision_date'] }}</p>@endif
 @if(!$loop->last)<div style="page-break-after:always"></div>@endif
@endforeach
</body></html>
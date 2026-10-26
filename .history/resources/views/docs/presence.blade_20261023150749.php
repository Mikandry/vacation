@php
 $idw   = max(25, min(60, (int) $s['presence_id_width']));
 $wN    = 2.0;     // colonne N° : la plus étroite du tableau
 $wIm   = 8.5;
 $wLoc  = 12.5;
 $wName = max($idw - $wN - $wIm - $wLoc, $wIm + $wLoc);   // nom et prénoms >= IM + localité
 $idTot = $wN + $wIm + $wLoc + $wName;
 $dayTot = 100 - $idTot;                                  // largeur totale des colonnes de dates
 $rh    = max(18, (int) $s['presence_row_height']);        // hauteur MINIMALE des lignes (points)
 $logo  = ($s['show_logo'] ?? 'OUI') === 'OUI' ? \App\Support\Logo::uri() : null;
 $real  = \App\Support\Logo::isReal();
 $left  = preg_split('/\R/', $s['presence_header_left']);
 $nSeries = count($series);
@endphp
<!doctype html><html><head><meta charset="utf-8"><style>
@page{margin:16px 26px}
body{font-family:'DejaVu Serif',serif;font-size:8pt;color:#000}
.c{text-align:center}
table.lh{width:100%;border-collapse:collapse;margin-bottom:8px} table.lh td{vertical-align:top;padding:0 4px}
table.p{width:100%;border-collapse:collapse;table-layout:fixed}
table.p th,table.p td{border:1px solid #000;padding:2px 3px;word-wrap:break-word}
table.p th{font-size:7.5pt;text-align:center;vertical-align:middle}
table.p td{vertical-align:middle;font-size:7.5pt;height:{{ $rh }}pt}
table.p td.num{text-align:center;padding:2px 0;font-size:7pt}
table.p th.num{padding:2px 0;font-size:7pt}
table.p td.im{white-space:nowrap;text-align:center}
thead{display:table-header-group} tr{page-break-inside:avoid}
.dt{font-size:7pt;font-weight:bold;line-height:1.1;height:34px}
</style></head><body>
@foreach($series as $si=>$sr)
 @if($logo)<div class="c" style="margin-bottom:4px"><img src="{{ $logo }}" height="{{ $real ? 52 : 40 }}">@if(!$real)<br><b style="font-size:7.5pt">{{ $s['republic'] }}</b>@endif</div>@endif
 <table class="lh"><tr>
  <td width="42%" class="c" style="font-size:7.5pt">@foreach($left as $l){{ $l }}<br>@endforeach</td>
  <td class="c">
   <b style="font-size:8.5pt">{{ $docs->tpl($s['presence_title'], get_defined_vars()) }}<br>{{ $docs->tpl($s['presence_line1'], get_defined_vars()) }}</b>
   @if(trim($s['presence_line2'])!=='')<br><span style="font-size:8pt">{{ $docs->tpl($s['presence_line2'], get_defined_vars()) }}</span>@endif
   @if($nSeries>1)<br><i style="font-size:7.5pt">Feuille {{ $si+1 }}/{{ $nSeries }} — jours {{ $sr['from'] }} à {{ $sr['to'] }}</i>@endif
  </td></tr></table>

 @php $dw = round($dayTot / $sr['cols'], 3); @endphp
 <table class="p" style="width:100%">
  <thead>
   {{-- 1re ligne : c'est elle qui fixe les largeurs de colonnes dans le PDF --}}
   <tr>
    <th rowspan="2" class="num" width="{{ $wN }}%" style="width:{{ $wN }}%">N°</th>
    <th rowspan="2" width="{{ $wName }}%" style="width:{{ $wName }}%">NOM ET PRENOMS</th>
    <th rowspan="2" width="{{ $wIm }}%" style="width:{{ $wIm }}%">IM</th>
    <th rowspan="2" width="{{ $wLoc }}%" style="width:{{ $wLoc }}%">LOCALITE DE SERVICE</th>
    <th colspan="{{ $sr['cols'] }}" width="{{ $dayTot }}%" style="width:{{ $dayTot }}%">EMARGEMENT</th>
   </tr>
   <tr>@for($k=0;$k<$sr['cols'];$k++)<th class="dt" width="{{ $dw }}%" style="width:{{ $dw }}%">@if($sr['labels'][$k]!==''){{ substr($sr['labels'][$k],0,5) }}<br>{{ substr($sr['labels'][$k],6) }}@endif</th>@endfor</tr>
  </thead>
  @foreach($agents as $i=>$ag)
  <tr>
   <td class="num" style="height:{{ $rh }}pt;@if($i===0)width:{{ $wN }}%@endif">{{ $i+1 }}</td>
   <td style="height:{{ $rh }}pt;@if($i===0)width:{{ $wName }}%@endif">{{ $ag->name }}</td>
   <td class="im" style="height:{{ $rh }}pt;@if($i===0)width:{{ $wIm }}%@endif">{{ $docs->fmtIm($ag->im) }}</td>
   <td class="c" style="height:{{ $rh }}pt;@if($i===0)width:{{ $wLoc }}%@endif">{{ $ag->locality }}</td>
   @for($k=0;$k<$sr['cols'];$k++)<td style="height:{{ $rh }}pt;@if($i===0)width:{{ $dw }}%@endif">&nbsp;</td>@endfor
  </tr>
  @endforeach
 </table>
 @if($loop->last)<p style="margin-top:10px">{{ $s['place'] }}, le {{ $s['decision_date'] }}</p>@endif
 @if(!$loop->last)<div style="page-break-after:always"></div>@endif
@endforeach
</body></html>
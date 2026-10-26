@php
 $idw   = max(25, min(60, (int) $s['presence_id_width']));
 $wN    = 2.0;     // colonne N° : la plus étroite du tableau
 $wIm   = 8.5;
 $wLoc  = 12.5;
 $wName = max($idw - $wN - $wIm - $wLoc, $wIm + $wLoc);   // nom et prénoms >= IM + localité
 $idTot = $wN + $wIm + $wLoc + $wName;
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
@foreach($parts as $pi=>$part)
@include('docs.parts.presence', $part + ['docs' => $docs])
@if(!$loop->last)<div style="page-break-after:always"></div>@endif
@endforeach
</body></html>
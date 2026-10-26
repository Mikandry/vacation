@php
 $fmt  = fn($x) => number_format((float) $x, 0, ',', ' ');
 $rate = $docs->rate($irsaRate);
 $logo = ($s['show_logo'] ?? 'OUI') === 'OUI' ? \App\Support\Logo::uri() : null;
 $real = \App\Support\Logo::isReal();
 $left = preg_split('/\R/', $s['doc_header']);
@endphp
<!doctype html><html><head><meta charset="utf-8"><style>
@page{margin:22px 30px} body{font-family:'DejaVu Sans',sans-serif;font-size:8pt}
h1{font-size:13pt;text-align:center;margin:4px 0 2px} .c{text-align:center} .r{text-align:right}
table.lh{width:100%;border-collapse:collapse;margin-bottom:8px} table.lh td{vertical-align:top;padding:0 4px}
table.d{width:100%;border-collapse:collapse} table.d th,table.d td{border:1px solid #000;padding:3px 4px;height:16px;word-wrap:break-word}
table.d th{background:#dde6f1;font-size:7.5pt} thead{display:table-header-group} tr{page-break-inside:avoid}
tr.sum td{font-weight:bold} tr.cum td{font-weight:bold;background:#dde6f1} td.im{white-space:nowrap}
</style></head><body>
@foreach($parts as $pi=>$part)
@include('docs.parts.decompte', $part + ['docs' => $docs])
@if(!$loop->last)<div style="page-break-after:always"></div>@endif
@endforeach
</body></html>
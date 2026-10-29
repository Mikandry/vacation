@php
 $num   = $a->note_number ?: $s['note_number'];
 $show  = ($s['show_logo'] ?? 'OUI') === 'OUI';
 $top   = ($s['note_logo_position'] ?? 'HAUT') === 'HAUT';
 $logo  = $show ? \App\Support\Logo::uri() : null;
 $real  = \App\Support\Logo::isReal();
 $left  = preg_split('/\R/', $s['note_header_left']);
 $sign  = preg_split('/\R/', $s['note_signature']);
@endphp
<!doctype html><html><head><meta charset="utf-8"><style>
@page{margin:22px 40px 60px 40px}
body{font-family:'Book Antiqua','DejaVu Serif',serif;font-size:10.5pt;color:#000}
.c{text-align:center} .j{text-align:justify}
table.h{width:100%;border-collapse:collapse} table.h td{vertical-align:top;padding:0 4px}
table.t{width:100%;border-collapse:collapse} table.t th,table.t td{border:1px solid #000;padding:4px 5px;height:22px;font-size:10pt}
table.t th{text-align:center} thead{display:table-header-group} tr{page-break-inside:avoid}
.sig{margin-left:50%;text-align:center}
</style></head><body>
@if($logo && $top)
<div class="c" style="margin-bottom:6px"><img src="{{ $logo }}" height="{{ $real ? 54 : 42 }}">@if(!$real)<br><b style="font-size:8pt">{{ $s['republic'] }}</b>@endif</div>
@endif
<table class="h"><tr>
 <td width="49%" class="c">@foreach($left as $l){{ $l }}<br>@endforeach</td>
 <td width="51%">
  @if($logo && !$top)<div class="c"><img src="{{ $logo }}" height="52"></div>@endif
  <div class="c" style="margin:6px 0"><b>{{ $s['note_title'] }}</b></div>
  <div class="j"><b>{{ $docs->tpl($num.' '.$s['note_subject'], get_defined_vars()) }}</b></div>
  <div class="c" style="margin-top:3px"><b>SESSION {{ $s['session'] }}</b></div>
 </td></tr></table>
<p class="j" style="margin-top:14px;line-height:1.6;text-indent:28px">{{ $docs->tpl($s['note_intro'], get_defined_vars()) }}</p>
@php
 // lignes du tableau : h = titre de sous-groupe, a = agent, f = arrêtage de sous-groupe
 $nrows = [];
 if ($quarantine) {
   $ni = 0;
   foreach ($groups as $g) {
     $nrows[] = ['h', mb_strtoupper($g['label'])];
     foreach ($g['rows'] as $ag) { $ni++; $nrows[] = ['a', $ni, $ag]; }
     $nrows[] = ['f', $docs->groupTail($g)];
   }
 } else {
   foreach ($agents as $i => $ag) $nrows[] = ['a', $i + 1, $ag];
 }
 // les 2 dernières lignes restent TOUJOURS avec l'arrêtage, la date et la signature (jamais l'arrêtage seul sur une page)
 $keep = min(2, count($nrows));
 $mainRows = array_slice($nrows, 0, count($nrows) - $keep);
 $tailRows = array_slice($nrows, count($nrows) - $keep);
 $theadHtml = '<thead><tr><th width="6%">N°</th><th width="51%">NOM ET PRENOMS</th><th width="14%">IM</th><th width="29%">LOCALITE</th></tr></thead>';
@endphp
@php
 // ligne invisible (hauteur 0) qui impose les mêmes largeurs de colonnes que le tableau du haut
 $spacer = '<tr><td width="6%" style="border:0;padding:0;height:0;font-size:0;line-height:0"></td><td width="51%" style="border:0;padding:0;height:0;font-size:0;line-height:0"></td><td width="14%" style="border:0;padding:0;height:0;font-size:0;line-height:0"></td><td width="29%" style="border:0;padding:0;height:0;font-size:0;line-height:0"></td></tr>';
@endphp
@if(count($mainRows))
<table class="t">{!! $theadHtml !!}
@include('docs.parts.noterows', ['rows' => $mainRows])
</table>
@endif
<table class="t" style="{{ count($mainRows) ? 'margin-top:-1px;' : '' }}page-break-inside:avoid">
@if(!count($mainRows)){!! $theadHtml !!}@endif
{!! $spacer !!}
@include('docs.parts.noterows', ['rows' => $tailRows])
<tr><td colspan="4" style="border:0;padding:0">
<p class="j" style="margin:14px 0 0;line-height:1.6">{{ $docs->tpl($s['note_closing'], get_defined_vars()) }}</p>
<p style="margin:0 0 0 50%;line-height:1.6;text-indent:28px">{{ $docs->tpl($s['note_place_date'], get_defined_vars()) }}</p>
@foreach($sign as $l)<p class="sig" style="margin-top:0;margin-bottom:0;line-height:1.6">{{ $l }}</p>@endforeach
</td></tr>
</table>
</body></html>

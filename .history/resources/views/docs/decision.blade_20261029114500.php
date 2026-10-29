@php
 $logo = ($s['show_logo'] ?? 'OUI') === 'OUI' ? \App\Support\Logo::uri() : null;
 $real = \App\Support\Logo::isReal();
@endphp
<!doctype html><html><head><meta charset="utf-8"><style>
@page{margin:22px 34px} body{font-family:'DejaVu Serif',serif;font-size:9pt;color:#000}
p{margin:0 0 4px} .j{text-align:justify} table.t{width:100%;border-collapse:collapse;margin-top:4px} table.t th,table.t td{border:1px solid #000;padding:2px 5px;font-size:8.5pt}
table.t th{text-align:center} .c{text-align:center} .tail{font-weight:bold;font-style:italic;margin:5px 0}
</style></head><body>
@if($logo)
<div class="c" style="margin-bottom:6px">
 <img src="{{ $logo }}" height="{{ $real ? 52 : 41 }}">
 @if(!$real)<br><b style="font-size:8pt">{{ $s['republic'] }}</b><br><i style="font-size:7pt">{{ $s['motto'] }}</i>@endif
</div>
@endif
<table width="100%"><tr><td width="38%" class="c" style="vertical-align:top">{{ $s['ministry'] }}<br>--------------</td>
<td class="j"><b>{{ $docs->decisionTitle(get_defined_vars()) }}</b>
<div class="c" style="margin-top:10px"><b>{{ $docs->decisionSession(get_defined_vars()) }}</b></div></td></tr></table>
<p class="c" style="margin-top:12px"><b>{{ $s['authority'] }}</b></p>
@foreach($visas as $v)<p class="j" style="font-size:8.6pt">{{ $v->text }}</p>@endforeach
<div style="page-break-before:always"></div>
<p><b>DECIDE :</b></p>
<p class="j">{{ $docs->article1(get_defined_vars()) }}</p>
@php
 $thead = '<thead><tr><th width="11%">N° d’ordre</th><th width="7%">n°</th><th>NOM ET PRENOMS</th><th width="13%">IM</th><th width="22%">LOCALITE DE SERVICE</th></tr></thead>';
 $spacer = '<tr><td width="11%" style="border:0;padding:0;height:0;font-size:0;line-height:0"></td><td width="7%" style="border:0;padding:0;height:0;font-size:0;line-height:0"></td><td style="border:0;padding:0;height:0;font-size:0;line-height:0"></td><td width="13%" style="border:0;padding:0;height:0;font-size:0;line-height:0"></td><td width="22%" style="border:0;padding:0;height:0;font-size:0;line-height:0"></td></tr>';
@endphp
@foreach($dpages as $pIdx => $pg)
@foreach($pg['runs'] as $run)
@php
 $items = collect($run['items'])->values();
 $isFinal = $run['final'];
 $keep = $isFinal ? min($run['keep'], $items->count()) : 0;      // les derniers noms restent avec l'arrêtage, les articles et la signature
 $main = $items->slice(0, $items->count() - $keep)->values();
 $kept = $items->slice($items->count() - $keep)->values();
@endphp
@if($run['label'])<p style="margin:8px 0 2px"><b>{{ mb_strtoupper($run['label']) }}</b></p>@endif
@if(!$isFinal)
<table class="t">{!! $thead !!}
@include('docs.parts.decrows', ['rows' => $items, 'quar' => $quarantine, 'from' => 0])
</table>
<p class="tail">{{ $run['tail'] }}</p>
@if($run['tail2'])<p class="tail">{{ $run['tail2'] }}</p>@endif
@else
@if($main->count())
<table class="t">{!! $thead !!}
@include('docs.parts.decrows', ['rows' => $main, 'quar' => $quarantine, 'from' => 0])
</table>
@endif
<table class="t" style="{{ $main->count() ? 'margin-top:-1px;' : '' }}page-break-inside:avoid">
@if(!$main->count()){!! $thead !!}@endif
{!! $spacer !!}
@include('docs.parts.decrows', ['rows' => $kept, 'quar' => $quarantine, 'from' => $main->count()])
<tr><td colspan="5" style="border:0;padding:0;font-size:9pt">
<p class="tail">{{ $run['tail'] }}</p>
@if($run['tail2'])<p class="tail">{{ $run['tail2'] }}</p>@endif
<p style="margin-top:8px"><b>ARRETEE LA PRESENTE DECISION AU NOMBRE DE {{ $nWords }} ({{ $n }}) AGENTS.</b></p>
@foreach($docs->articles(get_defined_vars()) as $art)
<p class="j" style="margin-top:6px">{{ $art['label'] }} : {{ $art['text'] }}</p>
@if($art['budget'])
<p>Cette indemnité est imputable sur la ligne budgétaire :</p>
<table class="t" style="width:100%"><tr><th>Mission</th><th>Indicateur d’objectif</th><th>Section</th><th>Compte</th><th>Code ORDSEC</th></tr>
<tr class="c"><td class="c">{{ $s['budget_mission'] }}</td><td class="c">{{ $s['budget_indicator'] }}</td><td class="c">{{ $s['budget_section'] }}</td><td class="c">{{ $s['budget_account'] }}</td><td class="c">{{ $s['budget_ordsec'] }}</td></tr></table>
@endif
@endforeach
<p style="text-align:right;margin-top:10px">{{ $docs->signDate(get_defined_vars()) }}</p>
<p style="text-align:right"><b>{{ $s['signatory'] }}</b></p>
</td></tr>
</table>
@endif
@endforeach
@if(!$loop->last)<div style="page-break-after:always"></div>@endif
@endforeach
</body></html>
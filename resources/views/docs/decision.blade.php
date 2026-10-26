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
<td class="j"><b>{{ $docs->decisionTitle(get_defined_vars()) }}</b></td></tr></table>
<p class="c" style="margin-top:12px"><b>{{ $s['authority'] }}</b></p>
@foreach($visas as $v)<p class="j" style="font-size:8.6pt">{{ $v->text }}</p>@endforeach
<div style="page-break-before:always"></div>
<p><b>DECIDE :</b></p>
<p class="j">{{ $docs->article1(get_defined_vars()) }}</p>
@if($quarantine)
@foreach($groups as $g)
<p style="margin:8px 0 2px"><b>{{ mb_strtoupper($g['label']) }}</b></p>
@foreach($g['chunks'] as $ch)
<table class="t"><thead><tr><th width="11%">N° d’ordre</th><th width="7%">n°</th><th>NOM ET PRENOMS</th><th width="13%">IM</th><th width="22%">LOCALITE DE SERVICE</th></tr></thead>
@foreach($ch['rows'] as $k=>$ag)<tr style="page-break-inside:avoid"><td class="c">{{ $ch['offset']+$k+1 }}</td><td class="c">{{ $ch['local']+$k+1 }}</td><td>{{ $ag->name }}</td><td class="c">{{ $ag->im }}</td><td class="c">{{ $ag->locality }}</td></tr>@endforeach</table>
@if($loop->last)<p class="tail">{{ $docs->groupTail($g) }}</p>@else<p class="tail">{{ $docs->pageTail($ch) }}</p><div style="page-break-after:always"></div>@endif
@endforeach
@endforeach
@else
@foreach($chunks as $ch)
<table class="t"><thead><tr><th width="11%">N° d’ordre</th><th width="7%">n°</th><th>NOM ET PRENOMS</th><th width="13%">IM</th><th width="22%">LOCALITE DE SERVICE</th></tr></thead>
@foreach($ch['rows'] as $k=>$ag)<tr><td class="c">{{ $ch['offset']+$k+1 }}</td><td class="c">{{ $k+1 }}</td><td>{{ $ag->name }}</td><td class="c">{{ $ag->im }}</td><td class="c">{{ $ag->locality }}</td></tr>@endforeach</table>
<p class="tail">{{ $docs->pageTail($ch) }}</p>
@if(!$loop->last)<div style="page-break-after:always"></div>@endif
@endforeach
@endif
<p style="margin-top:8px"><b>ARRETEE LA PRESENTE DECISION AU NOMBRE DE {{ $nWords }} ({{ $n }}) AGENTS.</b></p>
<p class="j" style="margin-top:6px">{{ $docs->article2(get_defined_vars()) }}</p>
<p>Cette indemnité est imputable sur la ligne budgétaire :</p>
<table class="t" style="width:100%"><tr><th>Mission</th><th>Indicateur d’objectif</th><th>Section</th><th>Compte</th><th>Code ORDSEC</th></tr>
<tr class="c"><td class="c">{{ $s['budget_mission'] }}</td><td class="c">{{ $s['budget_indicator'] }}</td><td class="c">{{ $s['budget_section'] }}</td><td class="c">{{ $s['budget_account'] }}</td><td class="c">{{ $s['budget_ordsec'] }}</td></tr></table>
<p style="margin-top:8px">Article 3 : La présente décision sera enregistrée et communiquée partout où besoin sera.</p>
<p style="text-align:right;margin-top:10px">{{ $docs->signDate(get_defined_vars()) }}</p>
<p style="text-align:right"><b>{{ $s['signatory'] }}</b></p>
</body></html>

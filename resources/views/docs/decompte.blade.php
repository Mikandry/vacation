@php $fmt = fn($x) => number_format((float) $x, 0, ',', ' '); $rate = $docs->rate($irsaRate); @endphp
<!doctype html><html><head><meta charset="utf-8"><style>
@page{margin:26px 30px} body{font-family:'DejaVu Sans',sans-serif;font-size:8pt}
h1{font-size:13pt;text-align:center;margin:6px 0 2px} .c{text-align:center} .r{text-align:right}
table{width:100%;border-collapse:collapse} th,td{border:1px solid #000;padding:3px 4px;height:16px}
th{background:#dde6f1;font-size:7.5pt} thead{display:table-header-group} tr{page-break-inside:avoid}
tr.sum td{font-weight:bold} tr.cum td{font-weight:bold;background:#dde6f1}
</style></head><body>
<b>{{ $s['ministry'] }}</b>
<h1>{{ $docs->tpl($s['decompte_title'], get_defined_vars()) }}</h1>
<p class="c" style="margin:0"><b>{{ $docs->tpl($s['decompte_line1'], get_defined_vars()) }}</b></p>
<p class="c" style="margin:0 0 6px"><i>{{ $docs->tpl($s['decompte_line2'], get_defined_vars()) }}</i></p>
@foreach($dec as $ci=>$ch)
<table>
 <thead><tr><th width="5%">N°</th><th>NOM ET PRENOMS</th><th width="9%">IM</th><th width="13%">LOCALITE</th><th width="6%">Jours</th><th width="9%">Taux/jour</th><th width="11%">Montant brut</th><th width="9%">IRSA {{ $rate }} %</th><th width="11%">Net à percevoir</th></tr></thead>
 @if(!$ch['first'])
 <tr class="sum"><td colspan="6" class="r">REPORT (page précédente)</td><td class="r">{{ $fmt($ch['report']['g']) }}</td><td class="r">{{ $fmt($ch['report']['i']) }}</td><td class="r">{{ $fmt($ch['report']['n']) }}</td></tr>
 @endif
 @foreach($ch['rows'] as $k=>$ag)
 <tr><td class="c">{{ $ch['offset']+$k+1 }}</td><td>{{ $ag->name }}</td><td class="c">{{ $ag->im }}</td><td class="c">{{ $ag->locality }}</td><td class="c">{{ $a->days }}</td><td class="r">{{ $fmt($a->rate) }}</td><td class="r">{{ $fmt($amount) }}</td><td class="r">{{ $fmt($irsaAmt) }}</td><td class="r">{{ $fmt($net) }}</td></tr>
 @endforeach
 <tr class="sum"><td colspan="6" class="r">TOTAL DE LA PAGE</td><td class="r">{{ $fmt($ch['page']['g']) }}</td><td class="r">{{ $fmt($ch['page']['i']) }}</td><td class="r">{{ $fmt($ch['page']['n']) }}</td></tr>
 <tr class="cum"><td colspan="6" class="r">{{ $loop->last ? 'TOTAL GÉNÉRAL' : 'TOTAL À REPORTER' }}</td><td class="r">{{ $fmt($ch['cum']['g']) }}</td><td class="r">{{ $fmt($ch['cum']['i']) }}</td><td class="r">{{ $fmt($ch['cum']['n']) }}</td></tr>
</table>
@if(!$loop->last)<div style="page-break-after:always"></div>@endif
@endforeach
<p style="margin-top:10px"><i>{{ $docs->tpl($s['decompte_footer'], get_defined_vars()) }}</i></p>
<p class="r" style="margin-top:12px">{{ $docs->signDate(get_defined_vars()) }}</p>
</body></html>
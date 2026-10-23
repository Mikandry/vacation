@php $logo = ($s['note_show_logo'] ?? 'OUI') === 'OUI' ? \App\Support\Logo::uri() : null; @endphp
<!doctype html><html><head><meta charset="utf-8"><style>
@page{margin:22px 34px} body{font-family:'DejaVu Sans',sans-serif;font-size:8.5pt} h1{font-size:15pt;text-align:center;margin:6px 0} .c{text-align:center} .r{text-align:right}
table{width:100%;border-collapse:collapse} th,td{border:1px solid #000;padding:3px 5px;height:16px;word-wrap:break-word} th{background:#dde6f1} thead{display:table-header-group} tr{page-break-inside:avoid}
td.im{white-space:nowrap}
</style></head><body>
@if($logo)<div class="c" style="margin-bottom:4px"><img src="{{ $logo }}" height="56"></div>@endif
<div class="c"><b>{{ $s['ministry'] }}</b></div>
<h1>{{ $docs->tpl($s['etat_title'], get_defined_vars()) }}</h1>
<p class="c"><b>{{ $docs->tpl($s['etat_line1'], get_defined_vars()) }}</b><br><i>{{ $docs->tpl($s['etat_line2'], get_defined_vars()) }}</i></p>
<table><thead><tr><th width="5%">N°</th><th>NOM ET PRENOMS</th><th width="11%">IM</th><th width="15%">LOCALITE</th><th width="6%">Jours</th><th width="10%">Taux/jour</th><th width="11%">Montant (Ar)</th><th width="13%">ÉMARGEMENT</th></tr></thead>
@foreach($agents as $i=>$ag)<tr><td class="c">{{ $i+1 }}</td><td>{{ $ag->name }}</td><td class="c im">{{ $docs->fmtIm($ag->im) }}</td><td class="c">{{ $ag->locality }}</td><td class="c">{{ $a->days }}</td><td class="r">{{ number_format($a->rate,0,',',' ') }}</td><td class="r">{{ number_format($amount,0,',',' ') }}</td><td></td></tr>@endforeach
<tr style="font-weight:bold"><td colspan="6" class="r">TOTAL ({{ $n }} agents)</td><td class="r">{{ number_format($totalMoney,0,',',' ') }}</td><td></td></tr></table>
<p style="margin-top:10px"><i>{{ $docs->tpl($s['etat_footer'], get_defined_vars()) }}</i></p>
<p class="r" style="margin-top:14px">{{ $docs->signDate(get_defined_vars()) }}</p>
</body></html>
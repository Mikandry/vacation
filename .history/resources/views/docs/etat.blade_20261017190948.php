<!doctype html><html><head><meta charset="utf-8"><style>
@page{margin:28px 34px} body{font-family:'DejaVu Sans',sans-serif;font-size:8.5pt} h1{font-size:15pt;text-align:center;margin:6px 0} .c{text-align:center} .r{text-align:right}
table{width:100%;border-collapse:collapse} th,td{border:1px solid #000;padding:3px 5px;height:16px} th{background:#dde6f1} thead{display:table-header-group} tr{page-break-inside:avoid}
</style></head><body>
<b>{{ $s['ministry'] }}</b><h1>ÉTAT DE VACATIONS</h1>
<p class="c"><b>Activité : {{ $a->name }} — Session {{ $s['session'] }}</b><br><i>{{ $a->days }} jour(s) × {{ number_format($a->rate,0,',',' ') }} Ar/jour — {{ $s['ref_arrete'] }}</i></p>
<table><thead><tr><th width="5%">N°</th><th>NOM ET PRENOMS</th><th width="10%">IM</th><th width="13%">LOCALITE</th><th width="6%">Jours</th><th width="11%">Taux/jour</th><th width="12%">Montant (Ar)</th><th width="15%">ÉMARGEMENT</th></tr></thead>
@foreach($agents as $i=>$ag)<tr><td class="c">{{ $i+1 }}</td><td>{{ $ag->name }}</td><td class="c">{{ $ag->im }}</td><td class="c">{{ $ag->locality }}</td><td class="c">{{ $a->days }}</td><td class="r">{{ number_format($a->rate,0,',',' ') }}</td><td class="r">{{ number_format($amount,0,',',' ') }}</td><td></td></tr>@endforeach
<tr style="font-weight:bold"><td colspan="6" class="r">TOTAL ({{ $n }} agents)</td><td class="r">{{ number_format($totalMoney,0,',',' ') }}</td><td></td></tr></table>
<p style="margin-top:10px"><i>Arrêté le présent état à la somme de {{ $totalWords }} ARIARY.</i></p>
<p class="r" style="margin-top:14px">{{ $docs->signDate(get_defined_vars()) }}</p>
</body></html>

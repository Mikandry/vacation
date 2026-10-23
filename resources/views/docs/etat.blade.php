@php
 $logo = ($s['show_logo'] ?? 'OUI') === 'OUI' ? \App\Support\Logo::uri() : null;
 $real = \App\Support\Logo::isReal();
 $left = preg_split('/\R/', $s['doc_header']);
@endphp
<!doctype html><html><head><meta charset="utf-8"><style>
@page{margin:22px 34px} body{font-family:'DejaVu Sans',sans-serif;font-size:8.5pt} h1{font-size:15pt;text-align:center;margin:4px 0} .c{text-align:center} .r{text-align:right}
table.lh{width:100%;border-collapse:collapse;margin-bottom:8px} table.lh td{vertical-align:top;padding:0 4px}
table.d{width:100%;border-collapse:collapse} table.d th,table.d td{border:1px solid #000;padding:3px 5px;height:16px;word-wrap:break-word} table.d th{background:#dde6f1} thead{display:table-header-group} tr{page-break-inside:avoid}
td.im{white-space:nowrap}
</style></head><body>
@if($logo)<div class="c" style="margin-bottom:4px"><img src="{{ $logo }}" height="{{ $real ? 56 : 42 }}">@if(!$real)<br><b style="font-size:8pt">{{ $s['republic'] }}</b>@endif</div>@endif
<table class="lh"><tr>
 <td width="42%" class="c" style="font-size:7.5pt">@foreach($left as $l){{ $l }}<br>@endforeach</td>
 <td class="c">
  <h1>{{ $docs->tpl($s['etat_title'], get_defined_vars()) }}</h1>
  <b>{{ $docs->tpl($s['etat_line1'], get_defined_vars()) }}</b><br><i>{{ $docs->tpl($s['etat_line2'], get_defined_vars()) }}</i>
 </td></tr></table>
<table class="d"><thead><tr><th width="5%">N°</th><th>NOM ET PRENOMS</th><th width="11%">IM</th><th width="15%">LOCALITE</th><th width="6%">Jours</th><th width="10%">Taux/jour</th><th width="11%">Montant (Ar)</th><th width="13%">ÉMARGEMENT</th></tr></thead>
@foreach($agents as $i=>$ag)<tr><td class="c">{{ $i+1 }}</td><td>{{ $ag->name }}</td><td class="c im">{{ $docs->fmtIm($ag->im) }}</td><td class="c">{{ $ag->locality }}</td><td class="c">{{ $a->days }}</td><td class="r">{{ number_format($a->rate,0,',',' ') }}</td><td class="r">{{ number_format($amount,0,',',' ') }}</td><td></td></tr>@endforeach
<tr style="font-weight:bold"><td colspan="6" class="r">TOTAL ({{ $n }} agents)</td><td class="r">{{ number_format($totalMoney,0,',',' ') }}</td><td></td></tr></table>
<p style="margin-top:10px"><i>{{ $docs->tpl($s['etat_footer'], get_defined_vars()) }}</i></p>
<p class="r" style="margin-top:14px">{{ $docs->signDate(get_defined_vars()) }}</p>
</body></html>

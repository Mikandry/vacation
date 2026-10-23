@php
 $fmt  = fn($x) => number_format((float)$x,0,',',' ');
 $logo = ($settings['show_logo'] ?? 'OUI') === 'OUI' ? \App\Support\Logo::uri() : null;
 $real = \App\Support\Logo::isReal();
@endphp
<!doctype html><html><head><meta charset="utf-8"><style>
@page{margin:22px 26px} body{font-family:'DejaVu Sans',sans-serif;font-size:7.5pt} h1{font-size:14pt;text-align:center;margin:4px 0}
table{width:100%;border-collapse:collapse} th,td{border:1px solid #666;padding:2px 4px} th{background:#1f3a5f;color:#fff} thead{display:table-header-group} tr{page-break-inside:avoid}
.c{text-align:center}.r{text-align:right}
</style></head><body>
@if($logo)<div class="c" style="margin-bottom:4px"><img src="{{ $logo }}" height="{{ $real ? 52 : 40 }}">@if(!$real)<br><b style="font-size:8pt">{{ $settings['republic'] }}</b>@endif</div>@endif
<h1>RÉCAPITULATIF DES ACTIVITÉS PAR AGENT — session {{ $settings['session'] }}</h1>
<p class="c">Verdict : <b>{{ $verdict }}</b> · {{ $stats['n'] }} agents mobilisables · moyenne {{ $fmt($stats['mean']) }} Ar · min {{ $fmt($stats['min']) }} · max {{ $fmt($stats['max']) }}</p>
<table><thead><tr><th>N°</th><th>NOM ET PRENOMS</th><th>IM</th><th>LOCALITE</th><th>ACTIVITÉS REÇUES</th><th>Nb</th><th>Montant (Ar)</th><th>Total (Ar)</th><th>Statut</th></tr></thead>
@foreach($rows as $i=>$r)<tr><td class="c">{{ $i+1 }}</td><td>{{ $r['agent']->name }}</td><td class="c">{{ $r['agent']->im }}</td><td class="c">{{ $r['agent']->locality }}</td><td>{{ $r['acts']->pluck('code')->implode(', ') }}</td><td class="c">{{ $r['count'] }}</td><td class="r">{{ $fmt($r['amount']) }}</td><td class="r">{{ $fmt($r['total']) }}</td><td class="c">{{ $r['status'] }}</td></tr>@endforeach
</table></body></html>

@php
 $fmt  = fn($x) => number_format((float) $x, 0, ',', ' ');
 $rate = $docs->rate($irsaRate);
 $logo = ($s['show_logo'] ?? 'OUI') === 'OUI' ? \App\Support\Logo::uri() : null;
 $real = \App\Support\Logo::isReal();
 $left = preg_split('/\R/', $s['doc_header']);
@endphp
@foreach($dec as $ci=>$ch)
@if($ci===0)
 @if($logo)<div class="c" style="margin-bottom:4px"><img src="{{ $logo }}" height="{{ $real ? 56 : 42 }}">@if(!$real)<br><b style="font-size:8pt">{{ $s['republic'] }}</b>@endif</div>@endif
 <table class="lh"><tr>
  <td width="42%" class="c" style="font-size:7.5pt">@foreach($left as $l){{ $l }}<br>@endforeach</td>
  <td class="c">
   <h1>{{ $docs->tpl($s['decompte_title'], get_defined_vars()) }}</h1>
   <b>{{ $docs->tpl($s['decompte_line1'], get_defined_vars()) }}</b><br><i>{{ $docs->tpl($s['decompte_line2'], get_defined_vars()) }}</i>
  </td></tr></table>
@endif
<table class="d">
 <thead><tr><th width="5%">N°</th><th>NOM ET PRENOMS</th><th width="10%">IM</th><th width="14%">LOCALITE</th><th width="6%">Jours</th><th width="9%">Taux/jour</th><th width="11%">Montant brut</th><th width="9%">IRSA {{ $rate }} %</th><th width="11%">Net à percevoir</th></tr></thead>
 @if(!$ch['first'])
 <tr class="sum"><td colspan="6" class="r">REPORT (page précédente)</td><td class="r">{{ $fmt($ch['report']['g']) }}</td><td class="r">{{ $fmt($ch['report']['i']) }}</td><td class="r">{{ $fmt($ch['report']['n']) }}</td></tr>
 @endif
 @foreach($ch['rows'] as $k=>$ag)
 <tr><td class="c">{{ $ch['offset']+$k+1 }}</td><td>{{ $ag->name }}</td><td class="c im">{{ $docs->fmtIm($ag->im) }}</td><td class="c">{{ $ag->locality }}</td><td class="c">{{ $a->days }}</td><td class="r">{{ $fmt($a->rate) }}</td><td class="r">{{ $fmt($amount) }}</td><td class="r">{{ $fmt($irsaAmt) }}</td><td class="r">{{ $fmt($net) }}</td></tr>
 @endforeach
 <tr class="sum"><td colspan="6" class="r">TOTAL DE LA PAGE</td><td class="r">{{ $fmt($ch['page']['g']) }}</td><td class="r">{{ $fmt($ch['page']['i']) }}</td><td class="r">{{ $fmt($ch['page']['n']) }}</td></tr>
 <tr class="cum"><td colspan="6" class="r">{{ $loop->last ? 'TOTAL GÉNÉRAL' : 'TOTAL À REPORTER' }}</td><td class="r">{{ $fmt($ch['cum']['g']) }}</td><td class="r">{{ $fmt($ch['cum']['i']) }}</td><td class="r">{{ $fmt($ch['cum']['n']) }}</td></tr>
</table>
@if(!$loop->last)<div style="page-break-after:always"></div>@endif
@endforeach
<p style="margin-top:10px"><i>{{ $docs->tpl($s['decompte_footer'], get_defined_vars()) }}</i></p>
<p class="r" style="margin-top:12px">{{ $docs->signDate(get_defined_vars()) }}</p>


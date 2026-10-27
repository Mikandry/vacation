@php
 $logo = ($s['show_logo'] ?? 'OUI') === 'OUI' ? \App\Support\Logo::uri() : null;
 $real = \App\Support\Logo::isReal();
 $left = preg_split('/\R/', $s['doc_header']);
@endphp
@if($logo)<div class="c" style="margin-bottom:4px"><img src="{{ $logo }}" height="{{ $real ? 56 : 42 }}">@if(!$real)<br><b style="font-size:8pt">{{ $s['republic'] }}</b>@endif</div>@endif
<table class="lh"><tr>
 <td width="42%" class="c" style="font-size:7.5pt">@foreach($left as $l){{ $l }}<br>@endforeach</td>
 <td class="c">
  <h1>{{ $docs->tpl($s['etat_title'], get_defined_vars()) }}</h1>
  <b>{{ $docs->tpl($s['etat_line1'], get_defined_vars()) }}</b><br><i>{{ $docs->tpl($s['etat_line2'], get_defined_vars()) }}</i>
 </td></tr></table>
<table class="d"><thead><tr><th colspan="8" style="text-align:right;background:#fff;font-weight:bold">Taux IRSA : {{ rtrim(rtrim(number_format($irsaRate,2,',',''),'0'),',') }} % — montant net à payer (IRSA déjà déduit)</th></tr><tr><th width="5%">N°</th><th>NOM ET PRENOMS</th><th width="11%">IM</th><th width="15%">LOCALITE</th><th width="6%">Jours</th><th width="10%">Taux/jour</th><th width="11%">Net à payer (Ar)</th><th width="13%">ÉMARGEMENT</th></tr></thead>
@foreach($agents as $i=>$ag)<tr><td class="c">{{ $i+1 }}</td><td>{{ $ag->name }}</td><td class="c im">{{ $docs->fmtIm($ag->im) }}</td><td class="c">{{ $ag->locality }}</td><td class="c">{{ $a->days }}</td><td class="r">{{ number_format($a->rate,0,',',' ') }}</td><td class="r">{{ number_format($net,0,',',' ') }}</td><td></td></tr>@endforeach
<tr style="font-weight:bold"><td colspan="6" class="r">TOTAL ({{ $n }} agents)</td><td class="r">{{ number_format($totalNet,0,',',' ') }}</td><td></td></tr></table>
<p style="margin-top:10px"><i>{{ $docs->etatFooter(get_defined_vars()) }}</i></p>
<p class="r" style="margin-top:14px">{{ $docs->signDate(get_defined_vars()) }}</p>


@php
 $idw   = max(25, min(60, (int) $s['presence_id_width']));
 $wN    = 2.0;     // colonne N° : la plus étroite du tableau
 $wIm   = 8.5;
 $wLoc  = 12.5;
 $wName = max($idw - $wN - $wIm - $wLoc, $wIm + $wLoc);   // nom et prénoms >= IM + localité
 $idTot = $wN + $wIm + $wLoc + $wName;
 $rh    = max(18, (int) $s['presence_row_height']);        // hauteur MINIMALE des lignes (points)
 $logo  = ($s['show_logo'] ?? 'OUI') === 'OUI' ? \App\Support\Logo::uri() : null;
 $real  = \App\Support\Logo::isReal();
 $left  = preg_split('/\R/', $s['presence_header_left']);
 $nSeries = count($series);
@endphp
@foreach($series as $si=>$sr)
 @if($logo)<div class="c" style="margin-bottom:4px"><img src="{{ $logo }}" height="{{ $real ? 52 : 40 }}">@if(!$real)<br><b style="font-size:7.5pt">{{ $s['republic'] }}</b>@endif</div>@endif
 <table class="lh"><tr>
  <td width="42%" class="c" style="font-size:7.5pt">@foreach($left as $l){{ $l }}<br>@endforeach</td>
  <td class="c">
   <b style="font-size:8.5pt">{{ $docs->tpl($s['presence_title'], get_defined_vars()) }}<br>{{ $docs->tpl($s['presence_line1'], get_defined_vars()) }}</b>
   @if(trim($s['presence_line2'])!=='')<br><span style="font-size:8pt">{{ $docs->tpl($s['presence_line2'], get_defined_vars()) }}</span>@endif
   @if($nSeries>1)<br><i style="font-size:7.5pt">Feuille {{ $si+1 }}/{{ $nSeries }} — jours {{ $sr['from'] }} à {{ $sr['to'] }}</i>@endif
  </td></tr></table>

 @php $dw = round((100-$idTot)/$sr['cols'],3); @endphp
 <table class="p">
  <colgroup><col style="width:{{ $wN }}%"><col style="width:{{ $wName }}%"><col style="width:{{ $wIm }}%"><col style="width:{{ $wLoc }}%">@for($k=0;$k<$sr['cols'];$k++)<col style="width:{{ $dw }}%">@endfor</colgroup>
  <thead>
   <tr><th rowspan="2" class="num">N°</th><th rowspan="2">NOM ET PRENOMS</th><th rowspan="2">IM</th><th rowspan="2">LOCALITE DE SERVICE</th><th colspan="{{ $sr['cols'] }}">EMARGEMENT</th></tr>
   <tr>@for($k=0;$k<$sr['cols'];$k++)<th class="dt">@if($sr['labels'][$k]!==''){{ substr($sr['labels'][$k],0,5) }}<br>{{ substr($sr['labels'][$k],6) }}@endif</th>@endfor</tr>
  </thead>
  @foreach($agents as $i=>$ag)
  <tr>
   <td class="num" style="height:{{ $rh }}pt">{{ $i+1 }}</td>
   <td style="height:{{ $rh }}pt">{{ $ag->name }}</td>
   <td class="im" style="height:{{ $rh }}pt">{{ $docs->fmtIm($ag->im) }}</td>
   <td class="c" style="height:{{ $rh }}pt">{{ $ag->locality }}</td>
   @for($k=0;$k<$sr['cols'];$k++)<td style="height:{{ $rh }}pt">&nbsp;</td>@endfor
  </tr>
  @endforeach
 </table>
  @if($loop->last)<p style="margin-top:10px;text-align:right;padding-right:20px">{{ $s['place'] }}, le {{ $s['decision_date'] ?: '……………………' }}</p>@endif
 <!-- @if($loop->last)<p style="margin-top:10px">{{ $s['place'] }}, le {{ $s['decision_date'] }}</p>@endif -->
 @if(!$loop->last)<div style="page-break-after:always"></div>@endif
@endforeach


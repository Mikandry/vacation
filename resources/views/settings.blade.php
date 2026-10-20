@extends('layouts.app')
@section('title','Paramètres')
@php
 $multi   = ['note_header_left'=>7,'note_signature'=>2,'note_intro'=>3,'note_subject'=>2,'note_closing'=>2,'presence_header_left'=>7,'presence_title'=>2];
 $selects = ['show_flag','note_show_logo','presence_show_dates'];
 $groups=[
  'Décision'=>[['direction','Direction'],['session','Session (année)'],['ministry','En-tête (ministère)'],['decision_number','N° de la décision'],['place','Lieu de signature'],['decision_date','Date de la décision (texte libre, ex. 15 octobre 2026)'],['authority','Autorité (ligne après l’en-tête)'],['signatory','Titre du signataire'],['ref_arrete','Référence de l’arrêté fixant les taux (Article 2)']],
  'Ligne budgétaire (Article 2)'=>[['budget_mission','Mission'],['budget_indicator','Indicateur d’objectif'],['budget_section','Section'],['budget_account','Compte'],['budget_ordsec','Code ORDSEC']],
  'En-tête de la décision (drapeau)'=>[['show_flag','Afficher le drapeau'],['republic','Ligne République'],['motto','Devise']],
  'Note de service'=>[['note_font','Police (Word)'],['note_show_logo','Afficher le logo (note, présence)'],['note_header_left','Bloc en-tête à gauche (une ligne par ligne)'],['note_title','Titre'],['note_number','N° par défaut (modifiable pour chaque activité)'],['note_subject','Objet (après le numéro)'],['note_intro','Phrase d’introduction'],['note_closing','Phrase « Arrêtée la présente note… »'],['note_place_date','Ligne « Fait à… »'],['note_signature','Signature (une ligne par ligne)']],
  'Feuille de présence'=>[['presence_header_left','Bloc en-tête à gauche (une ligne par ligne)'],['presence_title','Titre'],['presence_line1','Ligne 1 sous le titre'],['presence_line2','Ligne 2 (laisser vide si inutile)'],['presence_show_dates','Afficher les dates sur chaque colonne (NON = colonnes de dates vides à remplir à la main)'],['presence_max_cols','Nombre maximum de colonnes de jours par feuille (15)'],['presence_row_height','Hauteur des lignes (points, 36 par défaut)'],['presence_id_width','Largeur des colonnes nom/IM/localité (% de la page, 25 à 60)']],
  'État de vacations'=>[['etat_title','Titre'],['etat_line1','Ligne 1'],['etat_line2','Ligne 2'],['etat_footer','Phrase de fin']],
  'État de décompte et IRSA'=>[['irsa_rate','Taux IRSA par défaut (%)'],['decompte_title','Titre'],['decompte_line1','Ligne 1'],['decompte_line2','Ligne 2'],['decompte_rows','Agents par page (total de page et report)'],['decompte_footer','Phrase de fin']],
 ];
@endphp
@section('content')
<h4>Paramètres</h4>
<form method="post" action="{{ route('settings.update') }}" class="row g-3">
 @csrf
 @foreach($groups as $title=>$fields)
  <div class="col-lg-6"><div class="card"><div class="card-header fw-bold">{{ $title }}</div><div class="card-body row g-2">
   @foreach($fields as [$k,$l])
    <div class="col-12"><label class="form-label mb-0 small">{{ $l }}</label>
     @if(in_array($k,$selects))
      <select name="{{ $k }}" class="form-select">@foreach(['OUI','NON'] as $v)<option @selected(old($k,$s[$k])==$v)>{{ $v }}</option>@endforeach</select>
     @elseif(isset($multi[$k]))
      <textarea name="{{ $k }}" rows="{{ $multi[$k] }}" class="form-control">{{ old($k,$s[$k]) }}</textarea>
     @else
      <input name="{{ $k }}" class="form-control" value="{{ old($k,$s[$k]) }}">
     @endif
    </div>
   @endforeach
  </div></div></div>
 @endforeach

 <div class="col-lg-6"><div class="card"><div class="card-header fw-bold">Équilibre et répartition</div><div class="card-body row g-2">
  <div class="col-md-6"><label class="form-label mb-0 small">Répartition automatique</label><select name="auto" class="form-select">@foreach(['OUI','NON'] as $v)<option @selected($s['auto']==$v)>{{ $v }}</option>@endforeach</select></div>
  <div class="col-md-6"><label class="form-label mb-0 small">Tenir compte de l’historique</label><select name="use_history" class="form-select">@foreach(['OUI','NON'] as $v)<option @selected($s['use_history']==$v)>{{ $v }}</option>@endforeach</select></div>
  <div class="col-md-12"><label class="form-label mb-0 small">Éviter les chevauchements de dates dans la répartition automatique</label><select name="avoid_overlap" class="form-select">@foreach(['OUI','NON'] as $v)<option @selected(($s['avoid_overlap'] ?? 'OUI')==$v)>{{ $v }}</option>@endforeach</select></div>
  <div class="col-md-4"><label class="form-label mb-0 small">Tolérance ± (% de la moyenne)</label><input type="number" step="0.1" name="tolerance" class="form-control" value="{{ old('tolerance',$s['tolerance']) }}"></div>
  <div class="col-md-4"><label class="form-label mb-0 small">Seuil « ÉQUILIBRÉ » (% agents)</label><input type="number" name="threshold_ok" class="form-control" value="{{ old('threshold_ok',$s['threshold_ok']) }}"></div>
  <div class="col-md-4"><label class="form-label mb-0 small">Seuil « ACCEPTABLE » (% agents)</label><input type="number" name="threshold_acc" class="form-control" value="{{ old('threshold_acc',$s['threshold_acc']) }}"></div>
  <div class="col-md-6"><label class="form-label mb-0 small">Pas des tranches de montant (Ar)</label><input type="number" name="band_step" class="form-control" value="{{ old('band_step',$s['band_step']) }}"></div>
  <div class="col-12"><label class="form-label mb-0 small">Services de la direction (une ligne : <code>CODE|Nom complet</code>, CODE = localité de service des agents)</label><textarea name="services" rows="5" class="form-control">{{ old('services',$s['services']) }}</textarea></div>
 </div></div></div>

 <div class="col-12"><div class="alert alert-info small mb-0">Jetons utilisables dans les titres et phrases :
  <code>{activite} {code} {titre} {TITRE} {session} {jours} {agents} {agents_lettres} {direction} {lieu} {date} {ministere} {ref_arrete} {numero} {taux} {montant} {total} {total_lettres} {irsa} {net_lettres} {periode} {debut} {fin}</code>
  — <code>{TITRE}</code> = intitulé de l’activité en majuscules.</div></div>
 <div class="col-12"><button class="btn btn-primary">Enregistrer</button></div>
</form>
@endsection
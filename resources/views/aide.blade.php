@extends('layouts.app')
@section('title','Aide et à propos')
@php $info = config('vacation'); @endphp
@section('content')
<div class="row g-3">
 <div class="col-lg-4">
  <div class="card text-center">
   <div class="card-body py-4">
    <img src="{{ asset('images/logo-app.svg') }}" width="96" height="96" alt="logo">
    <h3 class="mt-3 mb-0" style="letter-spacing:.14em;color:var(--navy2)">{{ strtoupper($info['name']) }}</h3>
    <div class="text-muted">{{ $info['tagline'] }}</div>
    <span class="badge text-bg-warning mt-2 fs-6">Version {{ $info['version'] }}</span>
    <hr>
    <div class="small text-muted">Auteur</div>
    <div class="fw-bold fs-5">{{ $info['author'] }}</div>
    <div class="small text-muted mt-2">{{ $info['organisation'] }}</div>
    <div class="small text-muted">© {{ $info['year'] }} — tous droits réservés</div>
   </div>
  </div>
  <div class="card mt-3">
   <div class="card-header"><i class="bi bi-lightning-charge"></i> Accès rapide</div>
   <div class="list-group list-group-flush">
    <a class="list-group-item list-group-item-action" href="{{ route('agents.import.form') }}"><i class="bi bi-upload"></i> Importer des agents (Excel)</a>
    <a class="list-group-item list-group-item-action" href="{{ route('activities.index') }}"><i class="bi bi-list-check"></i> Régler les activités et quotas</a>
    <a class="list-group-item list-group-item-action" href="{{ route('choix.matrix') }}"><i class="bi bi-hand-index-thumb"></i> Choisir les agents à la main</a>
    <a class="list-group-item list-group-item-action" href="{{ route('documents.index') }}"><i class="bi bi-file-earmark-text"></i> Générer les documents</a>
    <a class="list-group-item list-group-item-action" href="{{ route('settings.index') }}"><i class="bi bi-gear"></i> Paramètres</a>
   </div>
  </div>
 </div>

 <div class="col-lg-8">
  <div class="card mb-3">
   <div class="card-header"><i class="bi bi-rocket-takeoff"></i> Mode d’emploi en 7 étapes</div>
   <div class="card-body">
    <ol class="mb-0">
     <li class="mb-2"><b>Paramètres</b> — renseignez la session, le n° de décision, le signataire, la ligne budgétaire, le taux IRSA (20 % par défaut) et la tolérance d’équilibre.</li>
     <li class="mb-2"><b>Activités</b> — ajoutez ou modifiez chaque activité : jours, taux par jour, <i>quota</i> (nombre d’agents de la direction), total prévu par le décret, <b>date de début</b> et n° de la note de service.</li>
     <li class="mb-2"><b>Agents</b> — importez la liste Excel ou ajoutez les agents un par un. <i>DIRECTION</i> = agent réparti automatiquement ; <i>EXTERNE</i> = personne ajoutée à part (autre direction, FIGN, PN, DREN…).</li>
     <li class="mb-2"><b>Répartition</b> — cliquez sur « Recalculer » : les agents les moins chargés sont désignés d’abord, pour un partage équitable.</li>
     <li class="mb-2"><b>Choix manuel</b> — cochez vous-même les agents de chaque activité (extérieurs compris). Vos choix sont conservés à chaque recalcul. Les <b>Exceptions F / X</b> permettent de forcer (F) ou d’exclure (X) un agent.</li>
     <li class="mb-2"><b>Tableau de bord</b> — vérifiez le verdict d’équilibre et le tableau « Contrôles » : chaque problème a un lien « Voir / résoudre ». Les <b>Conflits de dates</b> listent les agents placés dans deux activités qui se chevauchent.</li>
     <li><b>Documents</b> — pour chaque activité : décision, note de service, feuille de présence, état de vacations, état de décompte (avec IRSA). Utilisez <b>👁 Aperçu</b> avant de télécharger en PDF, Word ou Excel.</li>
    </ol>
   </div>
  </div>

  <div class="card mb-3">
   <div class="card-header"><i class="bi bi-book"></i> Lexique</div>
   <div class="table-responsive"><table class="table table-sm mb-0">
    <tr><th style="width:26%">Quota</th><td>Nombre d’agents de la direction à désigner pour une activité.</td></tr>
    <tr><th>F (forcer)</th><td>L’agent est toujours désigné pour l’activité, même au-delà du quota.</td></tr>
    <tr><th>X (exclure)</th><td>L’agent n’est jamais désigné pour l’activité.</td></tr>
    <tr><th>Historique</th><td>Montants déjà perçus : plus il est élevé, moins l’agent est choisi (équité sur plusieurs sessions).</td></tr>
    <tr><th>Tolérance</th><td>Écart accepté autour de la moyenne (20 % par défaut) pour dire qu’un agent est « équilibré ».</td></tr>
    <tr><th>IRSA</th><td>Impôt sur les revenus : taux unique, 20 % par défaut, modifiable dans Paramètres ou au moment de l’aperçu du décompte.</td></tr>
    <tr><th>Report</th><td>Dans l’état de décompte, total de la page précédente reporté en tête de la page suivante.</td></tr>
    <tr><th>Clôturer la session</th><td>Ajoute les montants de la session à l’historique des agents et vide les affectations.</td></tr>
   </table></div>
  </div>

  <div class="card mb-3">
   <div class="card-header"><i class="bi bi-tools"></i> Problèmes fréquents</div>
   <div class="card-body small">
    <p class="mb-2"><b>Le logo n’apparaît pas.</b> Placez <code>logo-madagascar.png</code> dans <code>public/images/</code> (le logo est toujours placé tout en haut des documents ; désactivable dans Paramètres).</p>
    <p class="mb-2"><b>Une activité n’a pas assez d’agents.</b> Tableau de bord → Contrôles → « Quota non atteint » → Voir / résoudre.</p>
    <p class="mb-2"><b>Les dates ne s’affichent pas sur la présence.</b> Renseignez la date de début de l’activité. Pour une feuille sans dates (à remplir à la main), utilisez « sans dates » dans Documents.</p>
    <p class="mb-0"><b>Après une mise à jour :</b> <code>php artisan migrate</code> puis <code>php artisan optimize:clear</code>.</p>
   </div>
  </div>

  <div class="card">
   <div class="card-header"><i class="bi bi-clock-history"></i> Notes de version</div>
   <div class="card-body small">
    <b>v1.0</b> — Répartition équilibrée, choix manuel et agents extérieurs, détection des conflits de dates, contrôles cliquables, décision, note de service, présence (15 colonnes max), état de vacations, état de décompte avec IRSA et reports de page, aperçu avant téléchargement, exports PDF / Word / Excel.
   </div>
  </div>
 </div>
</div>
@endsection

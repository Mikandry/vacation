<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Répartition des agents')</title>
<head><link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
 body{background:#f4f6f9;font-size:.92rem} .kpi{background:#fff;border-radius:.6rem;padding:.8rem 1rem;box-shadow:0 1px 3px rgba(0,0,0,.08)}
 .kpi .v{font-size:1.5rem;font-weight:700;color:#1f3a5f} .kpi .l{font-size:.75rem;color:#666;text-transform:uppercase}
 .table-sm td,.table-sm th{vertical-align:middle} .dot{display:inline-block;width:.9rem;height:.9rem;border-radius:50%;background:#1976d2}
 .st-Équilibré{background:#c8e6c9}.st-Sur-chargé{background:#ffcdd2}.st-Sous-chargé{background:#ffe0b2}.st-Externe{background:#e1f5fe}.st-Indisponible{background:#eee;color:#777}
 .sticky th{position:sticky;top:0;background:#1f3a5f;color:#fff;z-index:2}
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-3">
 <div class="container-fluid">
  <a class="navbar-brand" href="{{ route('dashboard') }}">Répartition {{ \App\Models\Setting::all_()['direction'] }}</a>
  <div class="navbar-nav flex-row flex-wrap gap-3">
   <a class="nav-link" href="{{ route('dashboard') }}">Tableau de bord</a>
   <a class="nav-link" href="{{ route('distribution.index') }}">Répartition</a>
   <a class="nav-link" href="{{ route('choix.matrix') }}">Choix manuel</a>
   <a class="nav-link" href="{{ route('activities.index') }}">Activités</a>
   <a class="nav-link" href="{{ route('agents.index') }}">Agents</a>
   <a class="nav-link" href="{{ route('exceptions.index') }}">Exceptions</a>
   <a class="nav-link" href="{{ route('documents.index') }}">Documents</a>
   <a class="nav-link" href="{{ route('visas.index') }}">Visas</a>
   <a class="nav-link" href="{{ route('settings.index') }}">Paramètres</a>
  </div>
 </div>
</nav>
<div class="container-fluid px-3">
 @if(session('ok'))<div class="alert alert-success">{{ session('ok') }}</div>@endif
 @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
 @yield('content')
</div>
@stack('scripts')
</body>
</html>

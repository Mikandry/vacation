@php
 $info = config('vacation');
 $set  = \App\Models\Setting::all_();
 $menu = [
  ['Pilotage', [
    ['dashboard', 'bi-speedometer2', 'Tableau de bord', 'dashboard'],
    ['conflicts.index', 'bi-exclamation-triangle', 'Conflits de dates', 'conflicts.*'],
  ]],
  ['Données', [
    ['agents.index', 'bi-people', 'Agents', 'agents.*'],
    ['activities.index', 'bi-list-check', 'Activités', 'activities.*'],
  ]],
  ['Répartition', [
    ['distribution.index', 'bi-diagram-3', 'Résultat', 'distribution.*'],
    ['choix.matrix', 'bi-hand-index-thumb', 'Choix manuel', 'choix.*'],
    ['exceptions.index', 'bi-toggles', 'Exceptions F / X', 'exceptions.*'],
  ]],
  ['Documents', [
    ['documents.index', 'bi-file-earmark-text', 'Générer les documents', 'documents.*'],
    ['visas.index', 'bi-journal-text', 'Visas / considérants', 'visas.*'],
  ]],
  ['Système', [
    ['settings.index', 'bi-gear', 'Paramètres', 'settings.*'],
    ['help', 'bi-question-circle', 'Aide et à propos', 'help'],
  ]],
 ];
@endphp
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Accueil') — {{ $info['name'] }}</title>
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
 :root{--navy:#14284a;--navy2:#1f3a5f;--gold:#d4a017;--bg:#f2f5f9;--side:250px}
 body{background:var(--bg);font-size:.92rem;color:#1d2733}
 .sidebar{position:fixed;inset:0 auto 0 0;width:var(--side);background:linear-gradient(180deg,var(--navy) 0%,#0e1d38 100%);color:#cfd9ea;display:flex;flex-direction:column;z-index:1040;transition:transform .25s}
 .brand{display:flex;align-items:center;gap:.7rem;padding:1.05rem 1rem;text-decoration:none;color:#fff;border-bottom:1px solid rgba(255,255,255,.08)}
 .brand svg{width:42px;height:42px;flex:none}
 .brand-name{font-weight:800;letter-spacing:.14em;font-size:1.15rem;line-height:1.1}
 .brand-sub{font-size:.68rem;color:#9fb3d1;line-height:1.2}
 .sidebar nav{flex:1;overflow-y:auto;padding:.4rem 0 1rem}
 .nav-group{font-size:.66rem;letter-spacing:.12em;text-transform:uppercase;color:#7f95b8;padding:.95rem 1.1rem .3rem}
 .nav-item{display:flex;align-items:center;gap:.7rem;padding:.5rem 1.1rem;margin:.05rem .6rem;border-radius:.5rem;color:#cfd9ea;text-decoration:none;font-size:.9rem}
 .nav-item i{font-size:1.05rem;width:1.2rem;text-align:center}
 .nav-item:hover{background:rgba(255,255,255,.08);color:#fff}
 .nav-item.active{background:var(--gold);color:#1b1500;font-weight:600}
 .side-foot{padding:.8rem 1.1rem;font-size:.72rem;color:#8ea3c4;border-top:1px solid rgba(255,255,255,.08)}
 .main{margin-left:var(--side);min-height:100vh;display:flex;flex-direction:column}
 .topbar{position:sticky;top:0;z-index:1030;background:#fff;border-bottom:1px solid #e3e8ef;padding:.6rem 1.25rem;display:flex;align-items:center;gap:.75rem}
 .page-title{font-weight:700;font-size:1.1rem;color:var(--navy2)}
 .content{padding:1.25rem;flex:1}
 .app-foot{padding:.7rem 1.25rem;font-size:.75rem;color:#7a8799;border-top:1px solid #e3e8ef;background:#fff;display:flex;flex-wrap:wrap;gap:.5rem;justify-content:space-between}
 @media(max-width:991px){.sidebar{transform:translateX(-100%)}.main{margin-left:0}body.side-open .sidebar{transform:none;box-shadow:0 0 40px rgba(0,0,0,.4)}}
 .card{border:0;box-shadow:0 1px 3px rgba(20,40,74,.08),0 1px 2px rgba(20,40,74,.06);border-radius:.7rem}
 .card-header{background:#fff;border-bottom:1px solid #eef1f6;font-weight:600;color:var(--navy2);border-radius:.7rem .7rem 0 0!important}
 .kpi{background:#fff;border-radius:.7rem;padding:.85rem 1rem;box-shadow:0 1px 3px rgba(20,40,74,.08);display:flex;gap:.8rem;align-items:center;height:100%}
 .kpi .ic{width:2.6rem;height:2.6rem;border-radius:.6rem;display:flex;align-items:center;justify-content:center;font-size:1.25rem;flex:none}
 .kpi .l{font-size:.7rem;color:#6b7788;text-transform:uppercase;letter-spacing:.04em;line-height:1.15}
 .kpi .v{font-size:1.35rem;font-weight:700;color:var(--navy2);line-height:1.15}
 .table-sm td,.table-sm th{vertical-align:middle}
 .dot{display:inline-block;width:.9rem;height:.9rem;border-radius:50%;background:#1976d2}
 .st-Équilibré{background:#c8e6c9}.st-Sur-chargé{background:#ffcdd2}.st-Sous-chargé{background:#ffe0b2}.st-Externe{background:#e1f5fe}.st-Indisponible{background:#eee;color:#777}
 .sticky th{position:sticky;top:0;background:var(--navy2);color:#fff;z-index:2}
 .btn-primary{background:var(--navy2);border-color:var(--navy2)}.btn-primary:hover{background:var(--navy);border-color:var(--navy)}
 .table-dark{--bs-table-bg:var(--navy2);--bs-table-border-color:#2a4a78}
 a{color:#1f5fae}
</style>
</head>
<body>
<aside class="sidebar" id="sidebar">
 <a class="brand" href="{{ route('dashboard') }}">
  <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
   <defs>
    <linearGradient id="lg1" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#2b5c9e"/><stop offset="1" stop-color="#14284a"/></linearGradient>
    <linearGradient id="lg2" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#ffe08a"/><stop offset="1" stop-color="#d4a017"/></linearGradient>
   </defs>
   <rect width="64" height="64" rx="14" fill="url(#lg1)" stroke="#3a6bb0" stroke-width="1.5"/>
   <circle cx="32" cy="29" r="17" fill="url(#lg2)" stroke="#fff3c4" stroke-width="2"/>
   <circle cx="32" cy="29" r="12.5" fill="none" stroke="#b8860b" stroke-width="1.5"/>
   <text x="32" y="35" font-family="Arial,sans-serif" font-size="16" font-weight="700" text-anchor="middle" fill="#7a5a00">Ar</text>
   <rect x="14" y="50" width="36" height="5" rx="2.5" fill="#fff" opacity=".85"/>
  </svg>
  <div><div class="brand-name">{{ strtoupper($info['name']) }}</div><div class="brand-sub">Répartition &amp; vacations</div></div>
 </a>
 <nav>
  @foreach($menu as [$group, $items])
   <div class="nav-group">{{ $group }}</div>
   @foreach($items as [$route, $icon, $label, $pattern])
    <a class="nav-item {{ request()->routeIs($pattern) ? 'active' : '' }}" href="{{ route($route) }}"><i class="bi {{ $icon }}"></i>{{ $label }}</a>
   @endforeach
  @endforeach
 </nav>
 <div class="side-foot"><b>{{ $info['name'] }}</b> v{{ $info['version'] }}<br>{{ $info['author'] }}</div>
</aside>

<div class="main">
 <header class="topbar">
  <button type="button" class="btn btn-light d-lg-none" onclick="document.body.classList.toggle('side-open')"><i class="bi bi-list fs-5"></i></button>
  <div class="page-title">@yield('title')</div>
  <div class="ms-auto d-flex align-items-center gap-2">
   <span class="badge text-bg-light border d-none d-md-inline"><i class="bi bi-building"></i> {{ $set['direction'] }}</span>
   <span class="badge text-bg-light border"><i class="bi bi-calendar3"></i> Session {{ $set['session'] }}</span>
   <form method="post" action="{{ route('distribution.run') }}" class="m-0">@csrf
    <button class="btn btn-sm btn-primary"><i class="bi bi-arrow-repeat"></i> <span class="d-none d-sm-inline">Recalculer</span></button></form>
  </div>
 </header>

 <main class="content">
  @if(session('ok'))<div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle"></i> {{ session('ok') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
  @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
  @yield('content')
 </main>

 <footer class="app-foot">
  <span><b>{{ $info['name'] }}</b> v{{ $info['version'] }} — {{ $info['tagline'] }}</span>
  <span>© {{ $info['year'] }} {{ $info['author'] }}</span>
 </footer>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>

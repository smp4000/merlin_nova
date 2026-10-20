<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow">
    <title>@yield('title', 'Partnerbereich') · Merlin Nova</title>
    <link rel="stylesheet" href="{{ asset('css/nova-registration.css') }}">
    <link rel="stylesheet" href="{{ asset('css/nova-partner.css') }}">
    <link rel="stylesheet" href="{{ asset('css/nova-employees.css') }}">
<link rel="stylesheet" href="{{ asset('css/nova-employee-inputs.css') }}"><script src="{{ asset('js/nova-employee-inputs.js') }}" defer></script>
</head>
<body class="partner-body">
@php
    // Status ausschließlich aus dem autorisierten Seitenmodell ableiten. Der Kopf
    // simuliert weder einen bezahlten Vertrag noch einen Test für technische Zugänge.
    $activePartner = $partner ?? null;
    $registeredTest = $activePartner !== null && $activePartner->registration_owner_id !== null;
    $activeTest = $registeredTest && $activePartner->allowsOperationalWrites();
@endphp
<a class="skip-link" href="#content">Zum Inhalt</a>
<svg class="icon-library" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><defs>
    <symbol id="nav-home" viewBox="0 0 24 24"><path d="m3 10 9-7 9 7M5 9v12h5v-7h4v7h5V9"/></symbol>
    <symbol id="nav-stations" viewBox="0 0 24 24"><path d="M4 21V4h10v17M2 21h14M7 7h4v5H7zM14 9h3l3 3v6a2 2 0 0 0 2-2V8l-3-3M18 5l3 3"/></symbol>
    <symbol id="nav-team" viewBox="0 0 24 24"><circle cx="9" cy="7" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3H3ZM16 4a3 3 0 0 1 0 6M18 14a5 5 0 0 1 3 5v2"/></symbol>
    <symbol id="nav-file" viewBox="0 0 24 24"><path d="M5 2h9l5 5v15H5zM14 2v6h5M8 12h8M8 16h8"/></symbol>
    <symbol id="nav-calendar" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="17" rx="2"/><path d="M7 2v6M17 2v6M3 11h18"/></symbol>
    <symbol id="nav-card" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 9h20M6 15h3"/></symbol>
    <symbol id="nav-settings" viewBox="0 0 24 24"><path d="m9 3 1-2h4l1 2 3 2 2 1 2 4-2 2v3l1 2-3 3-2-1-3 1-2 2-4-1v-3l-2-2-3-1v-4l2-1 1-3z"/><circle cx="12" cy="12" r="3"/></symbol>
    <symbol id="nav-plus" viewBox="0 0 24 24"><path d="M12 4v16M4 12h16"/></symbol>
    <symbol id="nav-arrow" viewBox="0 0 24 24"><path d="m9 5 7 7-7 7"/></symbol>
    <symbol id="nav-search" viewBox="0 0 24 24"><circle cx="10" cy="10" r="7"/><path d="m15 15 6 6"/></symbol>
</defs></svg>
<header class="app-topbar">
    <a class="app-wordmark" href="{{ route('workspace') }}"><span class="app-logo" aria-hidden="true">M</span>Merlin Nova</a>
    <div class="app-account-status" aria-label="Vertragsstatus">
        <span class="topbar-status"><i class="status-dot {{ $activeTest ? 'is-active' : '' }}" aria-hidden="true"></i>{{ $activeTest ? 'Kostenloser Test aktiv' : ($registeredTest ? 'Test noch nicht gestartet oder abgelaufen' : 'Partnerbereich') }}</span>
        <span class="topbar-status subscription-status"><i class="status-dot" aria-hidden="true"></i>Kein kostenpflichtiges Abo aktiv</span>
    </div>
    <details class="account-menu"><summary aria-label="Kontomenü öffnen"><span class="account-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span></summary>
        <div class="account-dropdown"><strong>{{ auth()->user()->name }}</strong><form method="post" action="{{ route('logout') }}">@csrf<button class="button secondary">Abmelden</button></form></div>
    </details>
</header>
<div class="app-shell">
    <aside class="app-sidebar" aria-label="Partnernavigation">
        <a class="sidebar-brand" href="{{ route('workspace') }}"><span class="app-logo" aria-hidden="true">M</span><span>Merlin Nova<small>{{ $activePartner?->name ?? 'Ihre Partnerzugänge' }}</small></span></a>
        <nav class="sidebar-nav" aria-label="Hauptnavigation">
            <a href="{{ $activePartner ? route('partner.overview', $activePartner->id).'#overview' : route('workspace') }}" @if(!$activePartner) aria-current="page" @endif><svg class="nav-icon" aria-hidden="true"><use href="#nav-home"/></svg>Übersicht</a>
            @if($activePartner)<a href="{{ route('partner.overview', $activePartner->id) }}#stations" @if(!request()->routeIs('employees.*')) aria-current="page" @endif><svg class="nav-icon" aria-hidden="true"><use href="#nav-stations"/></svg>Stationen</a>@endif
            @if($activePartner)@can('viewAny', [\App\Models\Employee::class, $activePartner])<a href="{{ route('employees.index', $activePartner->id) }}" @if(request()->routeIs('employees.*')) aria-current="page" @endif><svg class="nav-icon" aria-hidden="true"><use href="#nav-team"/></svg>Mitarbeiter</a>@endcan @endif
            @foreach(['nav-file' => 'Verträge', 'nav-calendar' => 'Einsatzplanung'] as $icon => $label)
                <span class="nav-unavailable"><svg class="nav-icon" aria-hidden="true"><use href="#{{ $icon }}"/></svg><span>{{ $label }}<small>In Vorbereitung</small></span></span>
            @endforeach
        </nav>
        <div class="sidebar-bottom">
            <span class="nav-unavailable"><svg class="nav-icon" aria-hidden="true"><use href="#nav-card"/></svg><span>Abo &amp; Rechnungen<small>In Vorbereitung</small></span></span>
            <span class="nav-unavailable"><svg class="nav-icon" aria-hidden="true"><use href="#nav-settings"/></svg><span>Einstellungen<small>In Vorbereitung</small></span></span>
            @if($activePartner)<a class="partner-switch" href="{{ route('workspace') }}">Partner wechseln</a>@endif
            @if(auth()->user()->is_super_admin)<a class="partner-switch" href="{{ url('/superadmin') }}">Plattformverwaltung</a>@endif
        </div>
    </aside>
    <div class="app-main-column"><main id="content" class="app-content" tabindex="-1">
        @include('shared.notifications')
        @if($errors->any())<div class="form-errors" role="alert"><strong>Bitte prüfen Sie Ihre Angaben.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @yield('content')
    </main><footer class="app-footer"><span>Merlin Nova</span><span>Lokale Entwicklungsumgebung · Keine automatische Kostenpflicht</span></footer></div>
</div>
</body></html>

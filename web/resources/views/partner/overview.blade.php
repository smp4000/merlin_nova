@extends('layouts.partner')
@section('title', 'Ihre Stationen')
@section('content')
<div class="partner-workspace">
<div class="page-heading" id="overview"><div><p class="eyebrow">Ihr Partnerbereich</p><h1>Stationen</h1><p class="page-subtitle">Ihre Tankstellenstandorte im Überblick.</p></div>
@can('create', [\App\Models\Station::class, $partner])
@if($partner->registration_owner_id === null || $stationCount < $partner->trial_station_limit)<a class="button primary" href="{{ route('stations.create', $partner->id) }}"><svg class="nav-icon" aria-hidden="true"><use href="#nav-plus"/></svg>Station anlegen</a>@else<span class="limit-notice">Stationslimit erreicht</span>@endif
@endcan</div>
<div class="overview-layout"><div class="overview-main">
<div class="partner-stats">
<section class="status-card"><span class="stat-label">Ihr Teststatus</span>
@if($partner->registration_owner_id === null)<strong>Technischer Partnerzugang</strong><p>Technischer Zugang ohne registrierten Testvertrag</p>
@elseif($partner->trial_started_at === null)<strong>Test noch nicht gestartet</strong><p>Test noch nicht gestartet · nur lesender Zugriff</p>
@elseif($partner->allowsOperationalWrites())<strong><i class="status-dot is-active" aria-hidden="true"></i>Kostenloser Test aktiv</strong><p>Bis {{ $partner->trial_ends_at->timezone('Europe/Berlin')->format('d.m.Y H:i') }} Uhr (Europe/Berlin)</p>
@else<strong>Test abgelaufen · nur lesender Zugriff</strong><p>Ihre Stationen bleiben sichtbar. Der kostenpflichtige Abschluss ist in Vorbereitung.</p>@endif
</section>
<section class="status-card"><span class="stat-label">Stationslimit</span><strong>{{ $stationCount }} <span class="stat-denominator">/ {{ $partner->registration_owner_id !== null ? $partner->trial_station_limit : 'ohne Testlimit' }}</span></strong><p>Stationen · Ihre zugesagten Testbedingungen bleiben erhalten.</p></section>
</div>
<section class="station-list-panel" id="stations"><div class="panel-heading"><h2>Ihre Stationen</h2><span class="count-badge">{{ $stationCount }} {{ $stationCount === 1 ? 'Standort' : 'Standorte' }}</span></div>
@forelse($stations as $station)
<article class="partner-station-row"><div class="station-symbol" aria-hidden="true"><svg class="nav-icon"><use href="#nav-stations"/></svg></div><div class="station-description"><h3>{{ $station->name }}</h3>@if($station->short_code)<small>Kurzcode {{ $station->short_code }}</small>@endif<p>{{ $station->street ?? 'Anschrift noch nicht erfasst' }} {{ $station->house_number }}@if($station->postal_code || $station->city), {{ $station->postal_code }} {{ $station->city }}@endif {{ $station->country_code }}</p>@if($station->brand)<small>{{ $station->brand }}</small>@endif</div>@can('viewDetails', $station)<a class="station-detail-link" href="{{ route('stations.details', [$partner->id,$station->id]) }}">Stammdaten <span aria-hidden="true">→</span></a>@endcan</article>
@empty<div class="empty-stations"><div class="station-symbol" aria-hidden="true"><svg class="nav-icon"><use href="#nav-stations"/></svg></div><h3>Ihre erste Station wartet auf Sie.</h3><p>Noch keine Station angelegt. Starten Sie mit den Grunddaten und der Anschrift Ihrer ersten Station.</p></div>@endforelse
{{ $stations->links() }}
@cannot('create', [\App\Models\Station::class, $partner])<p class="read-only-note">Für Ihren Zugang sind derzeit keine Stationsänderungen freigegeben.</p>@endcannot
</section>
<p class="overview-note">Keine automatische Kostenpflicht oder Abbuchung. Neue Standorte erweitern Ihren Tarif nicht automatisch.</p>
</div>
<aside class="quick-start"><h2>Schnell starten</h2><ol><li class="is-current"><span class="quick-number">1</span><div><strong>Erste Station erfassen</strong><p>Neuen Standort erfassen und Stammdaten hinterlegen.</p>@can('create', [\App\Models\Station::class, $partner])@if($partner->registration_owner_id === null || $stationCount < $partner->trial_station_limit)<a href="{{ route('stations.create', $partner->id) }}">Jetzt starten <span aria-hidden="true">→</span></a>@endif @endcan</div></li><li><span class="quick-number">2</span><div><strong>Mitarbeiter erfassen</strong><p>Einladen, Stammdaten ergänzen und prüfen.</p>@can('viewAny', [\App\Models\Employee::class, $partner])<a href="{{ route('employees.index', $partner->id) }}">Mitarbeiter öffnen →</a>@else<small>Personalrechte erforderlich</small>@endcan</div></li><li><span class="quick-number">3</span><div><strong>Einsätze planen</strong><p>Schichtpläne und Verfügbarkeiten im Blick behalten.</p><small>In Vorbereitung</small></div></li></ol><div class="quick-help"><strong>Schritt für Schritt eingerichtet.</strong><p>Sie benötigen für die manuelle Stationsanlage keinen API-Schlüssel.</p></div></aside>
</div>
</div>
@endsection

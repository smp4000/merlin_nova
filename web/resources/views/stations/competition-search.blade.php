@extends('layouts.partner')
@section('title','Wettbewerber auswählen')
@section('content')
<link rel="stylesheet" href="{{ asset('css/nova-station-map.css') }}">
@if(isset($draft['result']['source_radius']))<div class="info-banner" role="status"><p>Gewünschter Stationsradius: {{ $draft['radius'] }} km. Tatsächlicher Quellradius: {{ $draft['result']['source_radius'] }} km um PLZ/Ort. @if($draft['result']['radius_limited'] ?? false)<strong>Die Quelle unterstützt höchstens 20 km. Die Suche bis {{ $draft['radius'] }} km ist daher nicht vollständig abgedeckt.</strong>@else Bei vorhandenen Koordinaten wird die Luftlinie zur eigenen Station vor Übernahme geprüft.@endif @if($draft['result']['truncated'] ?? false)Die Trefferliste ist auf 50 Einträge begrenzt; weitere Quelltreffer werden nicht angezeigt.@endif</p></div>@endif
<div class="station-workspace"><div class="page-heading"><div><p class="eyebrow">{{ $station->name }} · Wettbewerb</p><h1>Wettbewerber auswählen</h1><p>Quelle: Benzinpreis-Aktuell.de. Übernahme erst nach Auswahl und Bestätigung.</p></div><a class="button secondary" href="{{ route('stations.details',[$partner->id,$station->id]).'#section-competition' }}">Zur Station</a></div>
@if(session('source_message'))<p role="status" class="info-banner">{{ session('source_message') }}</p>@endif
@if($canWrite)<section class="employee-card"><form method="post">@csrf<input type="hidden" name="action" value="search"><div class="station-form-grid"><div class="field"><label for="competition-radius">Umkreis (2–25 km)</label><input type="number" id="competition-radius" name="radius" min="2" max="25" step="1" value="{{ old('radius',$draft['radius'] ?? 5) }}" required></div><div><p>{{ $station->postal_code }} {{ $station->city }}</p><button class="button primary">Wettbewerber suchen</button></div></div><p class="field-help">Quellsuche um PLZ/Ort. Das Suchzentrum der Quelle kann von Ihren Stationskoordinaten abweichen. Nach der Suche werden die Entfernungen automatisch nacheinander geprüft; Anbietergrenzen bleiben wirksam.</p></form></section>@endif
@if(isset($draft['result']))
<section id="competition-results" class="employee-card"><h2>Wettbewerber auswählen · A–Z</h2><p class="field-help">Gewünschter Stationsradius: {{ $draft['radius'] }} km. Mehrere Standorte markieren, gemeinsam prüfen und anschließend zusammen übernehmen. Maximal acht einschließlich vorhandener Einträge.</p>
@php $existingIds=$competitors->pluck('source_id')->all(); $scanActive=count($scanPending)>0 && !isset($draft['distance_scan']['error']) && !($draft['distance_scan']['paused'] ?? false); @endphp
@if(\App\Stations\StationMap::distance($stationCoordinates, $stationCoordinates) === null)<div class="info-banner"><p>Bitte zuerst Breiten- und Längengrad der eigenen Station unter „Allgemein“ hinterlegen. Ohne diesen Ausgangspunkt bleiben Treffer gesperrt.</p></div>@endif
@if(isset($draft['distance_scan']))
<p role="status">Automatische Entfernungsprüfung: {{ count($sortedHits)-count($scanPending) }} von {{ count($sortedHits) }} Treffern bearbeitet. Ungeprüfte Treffer bleiben gesperrt. Auswahl nach Abschluss oder Anhalten der Prüfung.</p>
@if(isset($draft['distance_scan']['error']))<div class="info-banner" role="alert"><p>{{ $draft['distance_scan']['error'] }} Die automatische Prüfung wurde angehalten. Bereits geprüfte Entfernungen bleiben erhalten.</p></div>@endif
@if(count($scanPending) && $canWrite)
<form method="post" data-distance-scan @if($scanActive && !$errors->any()) data-continue @endif>@csrf
<input type="hidden" name="action" value="check_distances"><input type="hidden" name="scan_token" value="{{ $draft['distance_scan']['token'] }}">
<button class="button secondary">Entfernungsprüfung fortsetzen</button> @if($scanActive)<button class="button secondary" name="action" value="pause_distances">Prüfung anhalten</button>@endif<p class="field-help">Nacheinander mit Abstand, kein Parallelabruf. Bei Anbietergrenzen anhalten; keine automatische Wiederholung. Ohne JavaScript per Button fortsetzen.</p>
</form>
@endif
@endif
<form method="post">@csrf<input type="hidden" name="action" value="select_batch">
<fieldset class="competition-choices"><legend>Suchtreffer alphabetisch nach Stationsname</legend>
@forelse($sortedHits as $hit)
@php $isOwn=$hit['source_id']===$station->external_source_id; $alreadyAdded=in_array($hit['source_id'],$existingIds,true); $check=$hitChecks[$hit['source_id']]; $isOutside=$check['outside']; $isUnknown=$check['distance'] === null; @endphp
<label class="competition-choice {{ $isOwn || $alreadyAdded || $isOutside || $isUnknown ? 'is-unavailable' : '' }}">
@if($canWrite)<input type="checkbox" name="source_ids[]" value="{{ $hit['source_id'] }}" @disabled($isOwn || $alreadyAdded || $isOutside || $isUnknown || $scanActive) @checked(!$isOwn && !$alreadyAdded && !$isOutside && !$isUnknown && in_array($hit['source_id'], old('source_ids', $draft['batch']['ids'] ?? []), true))>@endif
<span><strong>{{ $hit['name'] }}</strong><small>{{ $hit['street'] ?? '' }}</small>
@if($check['distance'] !== null)<small><strong>{{ number_format($check['distance'], 2, ',', '.') }} km Luftlinie</strong> zur eigenen Station</small>@else<small>Entfernung ungeprüft oder Koordinaten fehlen · nicht auswählbar.</small>@endif
@if($isOutside)<small>Außerhalb des gewählten Radius von {{ $draft['radius'] }} km · nicht auswählbar</small>@endif</span>
@if($canWrite && !$isOwn && !$alreadyAdded && $check['distance'] === null)<button type="submit" class="button secondary" form="competition-check-{{ $hit['source_id'] }}" aria-label="Entfernung prüfen: {{ $hit['name'] }} {{ $hit['street'] ?? '' }}">Entfernung prüfen</button>@endif
@if($isOwn)<small>Eigene Station</small>@elseif($alreadyAdded)<small>Bereits übernommen</small>@endif
</label>
@empty<p>Keine auswählbaren Treffer. Bitte Hinweise zur Datenquelle beachten.</p>@endforelse
</fieldset>
@if($canWrite && count($sortedHits))<div class="competition-choice-actions"><button class="button primary">Auswahl prüfen</button><small>Erst die gemeinsame Bestätigung speichert die geprüfte Auswahl.</small></div>@endif
</form>
<p class="field-help">Entfernungen sind Luftlinien von der eigenen Station. Unbekannte Entfernungen werden nicht aus dem Ortsmittelpunkt geschätzt. „Entfernung prüfen“ lädt nur den angeklickten Standort; ungeprüfte Treffer bleiben bis zu einer gültigen Entfernungsprüfung gesperrt.</p>
@if($canWrite)
@foreach($sortedHits as $hit)
<form id="competition-check-{{ $hit['source_id'] }}" method="post">@csrf<input type="hidden" name="action" value="check_hit"><input type="hidden" name="source_id" value="{{ $hit['source_id'] }}"></form>
@endforeach
@endif
</section>@endif
@if(isset($draft['batch']))
<section id="competition-preview" class="employee-card"><h2>Auswahl gemeinsam prüfen</h2>
<p role="status">{{ count($draft['batch']['previews']) }} von {{ count($draft['batch']['ids']) }} Standorten geprüft. Noch nichts übernommen.</p>
@php $hasOutsideSelection = false; @endphp
@foreach($draft['batch']['previews'] as $source)
@php
    $distance = \App\Stations\StationMap::distance($stationCoordinates, $source['data']['coordinates'] ?? []);
    $isOutside = $distance !== null && $distance > $draft['radius'];
    $hasOutsideSelection = $hasOutsideSelection || $isOutside;
@endphp
<details class="employee-section" open><summary>{{ $source['data']['name'] }}</summary><div class="employee-section-content">
@include('stations.source-preview',['source'=>$source])
<p>Koordinaten: {{ $source['data']['coordinates']['latitude'] ?? 'nicht vorhanden' }} / {{ $source['data']['coordinates']['longitude'] ?? 'nicht vorhanden' }}. Ohne Koordinaten erscheint kein Kartenpin.</p>
@if($distance !== null)<p>Entfernung zur eigenen Station: <strong>{{ number_format($distance, 2, ',', '.') }} km</strong> Luftlinie. Gewählter Stationsradius: {{ $draft['radius'] }} km.</p>@else<p class="field-help">Die Entfernung lässt sich ohne beide Koordinatenpaare nicht prüfen.</p>@endif
@if($isOutside)<div role="alert" class="info-banner"><p><strong>Außerhalb des gewählten Stationsradius.</strong> Bitte diesen Standort oben abwählen und „Auswahl prüfen“ wählen oder mit größerem Radius neu suchen. Noch nichts übernommen.</p></div>@endif
</div></details>
@endforeach
@if(isset($draft['batch']['error']))<p role="alert" class="info-banner">{{ $draft['batch']['error'] }} Die Prüfung wurde angehalten. Sie können sie später fortsetzen oder die Auswahl ändern.</p>@endif
@if($canWrite)
@if(count($draft['batch']['previews']) < count($draft['batch']['ids']))
<form method="post" data-competition-check @if(!isset($draft['batch']['error']) && !$errors->any()) data-continue @endif>@csrf
<input type="hidden" name="action" value="check_batch"><input type="hidden" name="batch_token" value="{{ $draft['batch']['token'] }}">
<button class="button secondary">Nächsten Standort prüfen</button><p class="field-help">Die markierte Auswahl wird nacheinander mit Abstand geprüft. Bei einem Quellfehler stoppt die Folge. Ohne JavaScript bitte jeden Prüfschritt anklicken.</p>
</form>
@else
<form method="post">@csrf<input type="hidden" name="action" value="add_batch"><input type="hidden" name="batch_token" value="{{ $draft['batch']['token'] }}">
<label class="check"><input type="checkbox" name="confirmed" value="1" required>Ich habe Anschriften, Preise und vorhandene Koordinaten aller ausgewählten Wettbewerber geprüft und möchte sie zusammen übernehmen.</label>
<button class="button primary" @disabled($hasOutsideSelection)>Auswahl zusammen übernehmen ({{ count($draft['batch']['ids']) }})</button>
</form>
@endif
@endif
</section>
@endif
<script src="{{ asset('js/nova-competition-selection.js') }}" defer></script>
@if(isset($draft['preview']))<section id="competition-preview" class="employee-card"><h2>Auswahl prüfen</h2>@include('stations.source-preview',['source'=>$draft['preview']])<p>Koordinaten: {{ $draft['preview']['data']['coordinates']['latitude'] ?? 'nicht vorhanden' }} / {{ $draft['preview']['data']['coordinates']['longitude'] ?? 'nicht vorhanden' }}. Ohne Koordinaten erscheint kein Kartenpin.</p>@if($canWrite)<form method="post">@csrf<input type="hidden" name="action" value="add"><label class="check"><input type="checkbox" name="confirmed" value="1" required>Adresse, Preise und vorhandene Koordinaten geprüft. Diesen Wettbewerber übernehmen.</label><button class="button primary">Als Wettbewerber übernehmen</button></form>@endif</section>@endif
<section class="employee-card"><h2>Gespeicherte Quell-Wettbewerber</h2><p class="field-help">Zusammen mit manuellen Einträgen maximal acht. Preisaktualisierung nur auf Klick, kein Hintergrundabruf.</p>@forelse($competitors as $competitor)<details class="employee-section"><summary>{{ $competitor->snapshot['data']['name'] }}</summary><div class="employee-section-content">@include('stations.source-preview',['source'=>$competitor->snapshot])@if($canWrite)<form method="post">@csrf<input type="hidden" name="competitor_id" value="{{ $competitor->id }}"><label class="check"><input type="checkbox" name="confirmed" value="1" required>Aktion für diesen Wettbewerber bestätigen.</label><button class="button secondary" name="action" value="refresh">Preise aktualisieren</button><button class="button secondary" name="action" value="remove">Verknüpfung entfernen</button></form>@endif</div></details>@empty<p>Noch keine Wettbewerber aus der Quelle übernommen. Manuelle Einträge bleiben in den Stationsstammdaten.</p>@endforelse</section></div>
@endsection

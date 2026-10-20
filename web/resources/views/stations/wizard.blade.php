@extends('layouts.partner')
@section('title', 'Station anlegen')
@section('content')
@include('stations.catalog-options')
@php
    $steps = ['Grunddaten', 'Daten prüfen', 'Anbindung prüfen', 'Abschließen'];
    $titles = ['Station erfassen', 'Daten prüfen', 'Anbindung prüfen', 'Station anlegen'];
@endphp
<div class="wizard-panel station-wizard">
    <header class="wizard-title"><h1><span>{{ str_pad($draft['step'], 2, '0', STR_PAD_LEFT) }}</span> {{ $titles[$draft['step'] - 1] }}</h1><a href="{{ route('partner.overview', $partner->id) }}">Zur Partnerübersicht</a></header>
    <div class="wizard-content">
        <ol class="wizard-steps" aria-label="Fortschritt der Stationsanlage">@foreach($steps as $label)<li class="{{ $loop->iteration < $draft['step'] ? 'is-complete' : '' }}" @if($loop->iteration === $draft['step']) aria-current="step" @endif><span class="step-circle" aria-hidden="true">{{ $loop->iteration < $draft['step'] ? '✓' : $loop->iteration }}</span><span>{{ $label }}</span></li>@endforeach</ol>
        <form method="post" class="station-form" action="{{ route('stations.create', $partner->id) }}">@csrf<input type="hidden" name="draft" value="{{ $token }}">
        @if($draft['step'] === 1)
            <div class="form-heading"><h2>Welche Station möchten Sie anlegen?</h2><p>Wählen Sie einen Suchtreffer oder erfassen Sie Ihren Standort manuell. Erst Ihre abschließende Bestätigung legt die Station an.</p></div>
            <div class="wizard-entry-layout"><section class="provider-preview" aria-labelledby="provider-heading">
                <div class="provider-heading"><h3 id="provider-heading">Tankstellensuche</h3><span class="source-badge">Benzinpreis-Aktuell.de</span></div>
                <p>Optional: Suche im Umkreis von 5 km in Deutschland. PLZ und Ort werden erst beim Suchen an die Quelle gesendet.</p>
                @if(session('source_message'))<p role="alert">{{ session('source_message') }}</p>@endif
                <div class="station-form-grid"><div class="field"><label for="search-postal-code">Postleitzahl für die Suche</label><input id="search-postal-code" name="search_postal_code" maxlength="5" inputmode="numeric" value="{{ old('search_postal_code', $draft['search']['query']['search_postal_code'] ?? '') }}"></div><div class="field"><label for="search-city">Ort für die Suche (optional)</label><input id="search-city" name="search_city" maxlength="100" value="{{ old('search_city', $draft['search']['query']['search_city'] ?? '') }}"></div></div>
                <button class="button secondary" name="action" value="source_search" formnovalidate>Stationen suchen</button>
                <p class="field-help">Die PLZ genügt: Beim Klick auf „Stationen suchen“ wird ein fehlender Ort ermittelt. Ein vorhandener Ort bleibt erhalten. Bei mehreren Orten bitte „Ort ermitteln“ verwenden und auswählen.</p>
                @include('stations.postal-lookup', ['postalInput' => 'search-postal-code', 'cityInput' => 'search-city'])
                @if(isset($draft['source']))<button class="button secondary" name="action" value="source_manual" formnovalidate>Quellenverknüpfung entfernen</button>@endif
                @if(($draft['search']['result']['status'] ?? '') === 'ok')
                    <fieldset class="source-hits"><legend>Station auswählen</legend>
                        @foreach(array_slice($draft['search']['result']['data'], 0, 50) as $hit)
                            <label class="check"><input type="radio" name="source_id" value="{{ $hit['source_id'] }}"><span><strong>{{ $hit['name'] }}</strong><br>{{ $hit['street'] ?? '' }}</span></label>
                        @endforeach
                    </fieldset>
                    <button class="button primary" name="action" value="source_select" formnovalidate>Auswahl prüfen</button>
                    <p><small>Quelle: <a href="{{ $draft['search']['result']['source_url'] }}" target="_blank" rel="noopener noreferrer">Benzinpreis-Aktuell.de</a> · Abruf: {{ \Carbon\Carbon::parse($draft['search']['result']['fetched_at'])->timezone('Europe/Berlin')->format('d.m.Y H:i') }}</small></p>
                @endif
                <p><a href="#manual-data">Station manuell anlegen ↓</a></p>
            </section>
            <section class="wizard-manual"><div id="manual-data" class="form-heading"><h2>Grunddaten</h2><p>Stationsname und Marke lassen sich unabhängig von einer Datenquelle erfassen.</p></div>
            <div class="station-form-grid"><div class="field"><label for="name">Stationsname <span aria-hidden="true">*</span></label><input id="name" name="name" maxlength="255" required value="{{ old('name', $draft['data']['name'] ?? '') }}" autocomplete="organization"></div><div class="field"><label for="brand">Marke (optional)</label><input id="brand" name="brand" list="station-options-brand" maxlength="100" value="{{ old('brand', $draft['data']['brand'] ?? '') }}"><small>Die Marke ist kein Nachweis einer offiziellen Partnerschaft.</small></div></div>
            </section></div>
        @elseif($draft['step'] === 2)
            <div class="form-heading"><h2>Stammdaten prüfen und ergänzen</h2><p>Bitte kontrollieren Sie Ihre Angaben und ergänzen Sie die Anschrift.</p></div>
            <div class="station-data-preview"><span class="station-symbol" aria-hidden="true"><svg class="nav-icon"><use href="#nav-stations"/></svg></span><div><strong>{{ $draft['data']['name'] }}</strong><small>{{ $draft['data']['brand'] ?? 'Keine Marke angegeben' }} · {{ isset($draft['source']) ? 'Aus Quelle übernommen – bitte prüfen' : 'Manuell erfasst' }}</small></div></div>
            <h3>Anschrift</h3><div class="station-form-grid">
            @foreach(['street' => 'Straße und Hausnummer', 'postal_code' => 'Postleitzahl', 'city' => 'Ort'] as $field => $label)<div class="field {{ $field === 'street' ? 'field-wide' : '' }}"><label for="{{ $field }}">{{ $label }} <span aria-hidden="true">*</span></label><input id="{{ $field }}" name="{{ $field }}" required maxlength="{{ $field === 'street' ? 255 : ($field === 'city' ? 100 : 20) }}" value="{{ old($field, $draft['data'][$field] ?? '') }}" autocomplete="{{ ['street' => 'street-address', 'postal_code' => 'postal-code', 'city' => 'address-level2'][$field] }}"></div>@endforeach
            <div class="field field-wide"><label for="country_code">Land</label><select id="country_code" name="country_code" autocomplete="country">@foreach(['DE' => 'Deutschland', 'AT' => 'Österreich', 'CH' => 'Schweiz'] as $code => $label)<option value="{{ $code }}" @selected(old('country_code', $draft['data']['country_code'] ?? 'DE') === $code)>{{ $label }}</option>@endforeach</select></div></div>
            <div class="info-banner"><span aria-hidden="true">i</span><p>Die Anschrift und eine spätere Suche bestätigen nicht die Betreiberberechtigung.</p></div>
            @include('stations.postal-lookup', ['postalInput' => 'postal_code', 'cityInput' => 'city', 'countryInput' => 'country_code'])
        @elseif($draft['step'] === 3)
            <div class="form-heading"><h2>{{ isset($draft['source']) ? 'Anbindung prüfen' : 'Optionale Tankstellensuche' }}</h2><p>{{ isset($draft['source']) ? 'Prüfen Sie den gewählten Standort und den Abrufstand der Quelle.' : 'Ihr Standort kann vollständig ohne externe Anbindung angelegt werden.' }}</p></div>
            @if(isset($draft['source']))
                @include('stations.source-preview', ['source' => $draft['source']])
                @include('stations.source-import-choices')
                <p>Die Quellenverknüpfung wird erst beim Abschluss gespeichert. Eine Auswahl bestätigt keine Betreiberberechtigung.</p>
            @else
                <div class="connection-card"><span class="station-symbol" aria-hidden="true"><svg class="nav-icon"><use href="#nav-stations"/></svg></span><div><h3>Manuelle Stationsanlage</h3><p>Grunddaten und Anschrift werden direkt in Ihrem Partnerbereich gespeichert.</p><span class="source-badge">Ohne API nutzbar</span></div></div>
                <div class="info-banner"><span aria-hidden="true">i</span><p>Keine externe Station ausgewählt. Ihre Station wird manuell angelegt. Zur Suche können Sie zu Schritt 1 zurückgehen.</p></div>
            @endif
        @else
            <div class="form-heading"><h2>Zusammenfassung</h2><p>Alles bereit für Ihren neuen Standort. Prüfen Sie die Angaben vor dem Anlegen.</p></div>
            <dl class="station-summary">@foreach(['name' => 'Stationsname', 'brand' => 'Marke', 'street' => 'Straße', 'postal_code' => 'Postleitzahl', 'city' => 'Ort', 'country_code' => 'Land'] as $field => $label)<div><dt>{{ $label }}</dt><dd>{{ $draft['data'][$field] ?? '–' }}</dd></div>@endforeach</dl>
            @if(isset($draft['source'])) @include('stations.source-preview', ['source' => $draft['source']]) @endif
            @if($draft['imports']['hours'] ?? false)<p>Ausgewählt: geprüfte Öffnungszeiten in unbelegte Wochentage übernehmen.</p>@endif
            @if($draft['imports']['coordinates'] ?? false)<p>Ausgewählt: geprüftes Koordinatenpaar in leere Felder übernehmen.</p>@endif
            <div class="info-banner"><span aria-hidden="true">i</span><p>{{ isset($draft['source']) ? 'Mit Quellenverknüpfung zu Benzinpreis-Aktuell.de.' : 'Manuell erfasst · keine Benzinpreisanbindung.' }} Der interne Kurzcode wird bei Anlage aus dem Ort vorgeschlagen. Gesellschafts- und Kassennummern ergänzen Sie anschließend getrennt in den Stammdaten. Die Station zählt zu Ihrem zugesagten Stationslimit. Es entsteht kein Abo und keine zusätzliche Gebühr.</p></div>
            <label class="check"><input type="checkbox" name="confirmed" value="1" required>Ich habe die Angaben geprüft und möchte diese Station anlegen.</label>
        @endif
        <div class="wizard-actions">@if($draft['step'] > 1)<button class="button secondary" name="action" value="back" formnovalidate>Zurück</button>@else<a class="button secondary" href="{{ route('partner.overview', $partner->id) }}">Abbrechen</a>@endif<button class="button primary" name="action" value="next">{{ $draft['step'] === 4 ? 'Station verbindlich anlegen' : ($draft['step'] === 1 ? 'Weiter zu den Stammdaten' : 'Weiter') }}<svg class="nav-icon" aria-hidden="true"><use href="#nav-arrow"/></svg></button></div>
        </form>
    </div>
</div>
@endsection

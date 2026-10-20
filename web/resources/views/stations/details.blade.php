@extends('layouts.partner')
@section('title', 'Stationsdaten')
@section('content')
@php
    $groups = \App\Stations\StationFields::GROUPS;
    $fields = \App\Stations\StationFields::fields();
    $tabs = ['address' => $groups['address'], 'general' => $groups['general'], 'finance' => 'Finanzen', 'hours' => $groups['hours'], 'shop' => $groups['shop'], 'photos' => 'Fotos', 'competition' => $groups['competition'], 'employees' => 'Mitarbeiter', 'map' => $groups['map']];
    // Fehler bleiben auch bei ausgeblendeten Haupttabs auffindbar. Die Gruppen-
    // Zuordnung nutzt denselben Feldkatalog wie die Servervalidierung.
    $groupErrors = [];
    foreach ($tabs as $group => $label) {
        $keys = array_keys($fields[$group] ?? []);
        $keys = [...$keys, ...match($group) {
            'address' => ['street','postal_code','city','country_code'],
            'general' => ['name','brand','short_code'], 'finance' => ['bank_accounts'],
            'hours' => ['opening_hours'], 'photos' => ['logo','photos'],
            'competition' => ['competitors'], 'shop' => ['fuel_types','services','additional_businesses'],
            default => [],
        }];
        $groupErrors[$group] = collect($errors->keys())->contains(fn($errorKey) => collect($keys)->contains(fn($key) => $errorKey === $key || str_starts_with($errorKey,$key.'.')));
    }
@endphp
<div class="station-workspace">
<div class="page-heading"><div><p class="eyebrow">Station · Stammdaten</p><h1>{{ $station->name }}</h1><p class="page-subtitle">Standortdaten pflegen und Ihren Betrieb Schritt für Schritt ergänzen.</p></div><a class="button secondary" href="{{ route('partner.overview', $partner->id) }}">Zur Stationsübersicht</a></div>
@if(!$canWrite)<div class="info-banner"><span aria-hidden="true">i</span><p>Nur lesender Zugriff. Ihre Stationsdaten bleiben sichtbar; Änderungen sind nach Testablauf gesperrt.</p></div>@endif
@include('stations.price-refresh')
<div class="station-details" data-station-details>
@if($station->external_source_data)
    <details class="source-disclosure" @if(session('source_message')) open @endif>
    <summary><strong>Benzinpreise</strong><span class="source-price-strip">@foreach(['e5' => 'E5', 'e10' => 'E10', 'diesel' => 'Diesel'] as $fuel => $label)<span>{{ $label }} <b>{{ isset($station->external_source_data['data']['prices'][$fuel]) ? str_replace('.', ',', $station->external_source_data['data']['prices'][$fuel]).' €/l' : '–' }}</b>@if($station->external_source_data['data']['prices_last_known'][$fuel] ?? false)<small>zuletzt bekannt</small>@endif</span>@endforeach</span><span class="source-summary-stamp">Benzinpreis-Aktuell.de · {{ \Carbon\Carbon::parse($station->external_source_data['fetched_at'])->timezone('Europe/Berlin')->format('d.m. H:i') }}@if(\Carbon\Carbon::parse($station->external_source_data['fetched_at'])->lt(now()->subMinutes(5))) · veraltet @endif</span><span class="source-summary-link">Quelle &amp; Übernahme</span></summary>
    <div class="source-disclosure-content">
    @include('stations.source-preview', ['source' => $station->external_source_data])
    <div class="source-actions">
    @if($canWrite)<a class="button secondary" href="{{ route('stations.source.import', [$partner->id, $station->id]) }}">Öffnungszeiten und Koordinaten prüfen</a>@endif
    @if(session('source_message'))<p role="alert">{{ session('source_message') }}</p>@endif
    @if($canWrite)<form method="post" action="{{ route('stations.source.refresh', [$partner->id, $station->id]) }}">@csrf<button class="button secondary">Benzinpreise aktualisieren</button><p><small>Einzelabruf auf Knopfdruck; innerhalb von fünf Minuten wird der letzte Abruf verwendet.</small></p></form>@endif
    </div></div></details>
@endif
@if(!$station->external_source_id && $canWrite)<p><a class="button secondary" href="{{ route('stations.source.configure', [$partner->id, $station->id]) }}">Benzinpreisanbindung einrichten</a></p>@endif
<nav class="station-tabs" aria-label="Stationsdatenbereiche">@foreach($tabs as $group => $label)<a id="tab-{{ $group }}" href="#section-{{ $group }}" data-station-tab="{{ $group }}">{{ $label }}@if($groupErrors[$group])<span class="field-error" aria-label="Fehler in diesem Bereich">!</span>@endif @if($group === 'employees')<small>In Vorbereitung</small>@elseif($group === 'finance')<small>Nur Inhaber</small>@endif</a>@endforeach</nav>
<form method="post" enctype="multipart/form-data" action="{{ route('stations.details', [$partner->id, $station->id]) }}" class="station-form details-form">@csrf @method('PATCH')<input type="hidden" name="version" value="{{ $profileVersion }}"><input type="hidden" name="snapshot" value="{{ $snapshot }}">
<datalist id="station-timezones"><option value="Europe/Berlin"><option value="Europe/Vienna"><option value="Europe/Zurich"><option value="UTC"></datalist>
@include('stations.catalog-options')
@foreach($tabs as $group => $groupLabel)
<section id="section-{{ $group }}" class="station-tab-panel" data-station-panel="{{ $group }}" aria-labelledby="tab-{{ $group }}" @if($groupErrors[$group]) data-field-error @endif>
<div class="form-heading"><h2>{{ $groupLabel }}</h2>
@if($group === 'address')<p>Anschrift und freiwillige geschäftliche Kontaktangaben Ihrer Station.</p>
@elseif($group === 'general')<p>Organisation, Markenname und interne Kennungen Ihrer Station.</p>
@elseif($group === 'hours')<p>Öffnungszeiten ausdrücklich erfassen. Leere Tage bleiben unbekannt; Öffnung über Mitternacht ist möglich.</p>
@elseif($group === 'shop')<p>Ausstattung, Waschbetrieb und Shopdaten. Nicht angegeben bedeutet weder Ja noch Nein.</p>
@elseif($group === 'competition')<p>Manuelle Wettbewerber und bewusst ausgewählte Standorte aus Benzinpreis-Aktuell.de. Maximal acht insgesamt.</p>
@elseif($group === 'map')<p>Eigene Station und gespeicherte Wettbewerber auf OpenStreetMap anzeigen. Die Karte erscheint automatisch beim Öffnen dieses Bereichs.</p>
@endif</div>
@if($group === 'employees' || ($group === 'finance' && !$canManageFinance))
<div class="info-banner"><span aria-hidden="true">i</span><div><strong>{{ $group === 'finance' ? 'Finanzdaten sind nur für Inhaber freigegeben.' : 'Dieser Fachbereich ist in Vorbereitung.' }}</strong>
@if($group === 'finance')<p>Stationsbankkonten und geschützte Geschäftsdaten sind ausschließlich für den Inhaber zugänglich. Ihre anderen Stationsdaten können Sie weiterhin entsprechend Ihrer Rolle pflegen.</p>
@else<p>Mitarbeiter werden später als eigene Personalakten mit Stationsrolle und Hauptstandort zugeordnet. Persönliche Partnerzugänge sind keine Mitarbeiterakten.</p>@endif
</div></div>
@else
<fieldset @disabled(!$canWrite) class="station-fields">
@if($group === 'address')
<div class="station-form-grid">@foreach(['street' => 'Straße und Hausnummer', 'postal_code' => 'Postleitzahl', 'city' => 'Ort'] as $baseKey => $baseLabel)<div class="field {{ $baseKey === 'street' ? 'field-wide' : '' }}" @if($errors->has($baseKey)) data-field-error @endif><label for="{{ $baseKey }}">{{ $baseLabel }} *</label><input id="{{ $baseKey }}" name="{{ $baseKey }}" value="{{ old($baseKey, $station->{$baseKey}) }}" maxlength="{{ $baseKey === 'street' ? 255 : ($baseKey === 'city' ? 100 : 20) }}" aria-required="true">@error($baseKey)<small class="field-error">{{ $message }}</small>@enderror</div>@endforeach
<div class="field"><label for="country_code">Land *</label><select id="country_code" name="country_code">@foreach(['DE' => 'Deutschland','AT' => 'Österreich','CH' => 'Schweiz'] as $code => $countryLabel)<option value="{{ $code }}" @selected(old('country_code',$station->country_code) === $code)>{{ $countryLabel }}</option>@endforeach</select></div></div><p class="field-help">Tragen Sie die Hausnummer entweder in der Straßenzeile oder im separaten Feld ein.</p>
@endif
@if($group === 'general')
<div class="station-form-grid"><div class="field" @if($errors->has('name')) data-field-error @endif><label for="name">Stationsname *</label><input id="name" name="name" value="{{ old('name',$station->name) }}" maxlength="255" aria-required="true">@error('name')<small class="field-error">{{ $message }}</small>@enderror</div><div class="field" @if($errors->has('short_code')) data-field-error @endif><label for="short_code">Interner Kurzcode *</label><input id="short_code" name="short_code" value="{{ old('short_code',$shortCodeSuggestion) }}" maxlength="8" pattern="[A-Za-z0-9]+(-[A-Za-z0-9]+)?" aria-required="true"><small>Für Pläne und interne Kennzeichnung, eindeutig je Partner. Höchstens 8 Zeichen aus Buchstaben, Ziffern und einem Bindestrich. Unabhängig von der Gesellschaftsnummer.</small>@error('short_code')<small class="field-error">{{ $message }}</small>@enderror</div><div class="field"><label for="brand">Marke (optional)</label><input id="brand" name="brand" list="station-options-brand" value="{{ old('brand',$station->brand) }}" maxlength="100"></div></div>
@endif
@if($group === 'hours')<details class="hours-extras" @if($groupErrors[$group]) open @endif><summary>Zeitzone: {{ old('profile.timezone', $profileData['timezone'] ?? '') ?: 'Nicht angegeben' }} · Eröffnungsdaten</summary>@endif
<div class="station-form-grid {{ $group === 'hours' ? 'hours-basics' : '' }}">@foreach($fields[$group] ?? [] as $key => [$label,$type,$limit])@include('stations.field',compact('key','label','type','limit'))@endforeach</div>
@if($group === 'hours')<p class="field-help hours-history-help">Eröffnungsdaten sind freiwillig. Keine automatische Voreinstellung.</p></details>@endif
@if($group === 'finance')
<div class="info-banner"><span aria-hidden="true">i</span><p>Diese Stationskonten sind Stammdaten. Sie erteilen kein SEPA-Mandat und lösen keine Zahlung aus. Es erfolgen keine Bankabfragen oder IBAN-Berechnungen.</p></div>
@php $accounts = $profileData['bank_accounts'] ?? []; @endphp
<div data-bank-list data-repeat-limit="10">@for($index=0;$index < min(10,max(count($accounts)+1,1));$index++)
<fieldset class="competitor-card" data-bank-row><legend>Bankkonto {{ $index+1 }}</legend><div class="station-form-grid">
@foreach(['iban'=>'IBAN','bank_name'=>'Bankname','bic'=>'BIC','description'=>'Beschreibung','account_type'=>'Kontotyp (eigene Eingabe möglich)'] as $accountKey=>$accountLabel)<div class="field"><label for="bank-{{ $index }}-{{ $accountKey }}">{{ $accountLabel }}</label><input id="bank-{{ $index }}-{{ $accountKey }}" name="profile[bank_accounts][{{ $index }}][{{ $accountKey }}]" @if($accountKey === 'account_type') list="station-options-account_type" @endif value="{{ $accounts[$index][$accountKey] ?? '' }}" autocomplete="off" maxlength="{{ $accountKey === 'iban' ? 50 : ($accountKey === 'bic' ? 11 : ($accountKey === 'account_type' ? 100 : 255)) }}"></div>@endforeach
</div><button type="button" class="button secondary" data-remove-bank hidden>Eintrag entfernen</button></fieldset>
@endfor</div><button type="button" class="button secondary" data-add-bank hidden>Bankkonto hinzufügen</button><p class="field-help">IBANs werden formal geprüft, nicht auf Kontoexistenz oder Inhaberschaft. Neue Bankeingaben werden bei Fehlern aus Sicherheitsgründen nicht wiederbefüllt. Bestehende Werte bleiben gespeichert.</p>
@endif
@if($group === 'photos')
<div class="station-form-grid"><div class="field"><label for="station-logo">Stationslogo (JPG/PNG, maximal 2 MB)</label><input id="station-logo" type="file" name="logo" accept="image/jpeg,image/png"></div><div class="field"><label for="station-photos">Stationsfotos (JPG/PNG, maximal 5 MB je Bild)</label><input id="station-photos" type="file" name="photos[]" multiple accept="image/jpeg,image/png"></div></div><p class="field-help">Maximal zehn Fotos und 20 Megapixel je Bild. Bilder werden ohne Originalnamen/EXIF-Daten privat gespeichert und nur berechtigten Stationsverwaltern ausgeliefert. Ersetzte Logos werden vorerst aufbewahrt; Löschung und Aufbewahrungsregeln folgen separat.</p>
<div class="station-gallery">@forelse($profileData['media'] ?? [] as $item)<figure><img src="{{ route('stations.image',[$partner->id,$station->id,$item['id']]) }}" alt="{{ $item['kind'] === 'logo' ? 'Stationslogo' : 'Stationsfoto' }}" loading="lazy"><figcaption>{{ $item['kind'] === 'logo' ? 'Logo' : 'Stationsfoto' }}</figcaption></figure>@empty<p>Noch keine Bilder hinterlegt.</p>@endforelse</div>
@endif
@if($group === 'hours')
<div class="hours-columns" aria-hidden="true"><span>Tag</span><span>Status</span><span>Von</span><span>Bis</span></div>
<div class="hours-table">@foreach(\App\Stations\StationFields::DAYS as $day => $dayLabel)
@php $hours = old('profile.opening_hours.'.$day, $profileData['opening_hours'][$day] ?? []); @endphp
<fieldset class="hours-day"><legend>{{ $dayLabel }}</legend><span class="hours-day-name" aria-hidden="true">{{ $dayLabel }}</span><div class="field"><label for="hours-{{ $day }}-status">Status {{ $dayLabel }}</label><select id="hours-{{ $day }}-status" name="profile[opening_hours][{{ $day }}][status]">@foreach(['unknown'=>'Nicht angegeben','closed'=>'Geschlossen','all_day'=>'24 Stunden geöffnet','interval'=>'Zeitintervall'] as $value=>$label)<option value="{{ $value }}" @selected(($hours['status'] ?? 'unknown') === $value)>{{ $label }}</option>@endforeach</select></div>
<div class="field"><label for="hours-{{ $day }}-open">Von {{ $dayLabel }}</label><input id="hours-{{ $day }}-open" name="profile[opening_hours][{{ $day }}][open]" type="time" value="{{ $hours['open'] ?? '' }}"></div><div class="field"><label for="hours-{{ $day }}-close">Bis {{ $dayLabel }}</label><input id="hours-{{ $day }}-close" name="profile[opening_hours][{{ $day }}][close]" type="time" value="{{ $hours['close'] ?? '' }}"></div>@if($errors->has('opening_hours.'.$day))<p class="field-error" data-field-error>{{ $errors->first('opening_hours.'.$day) }}</p>@endif</fieldset>
@endforeach</div><p class="field-help">Bei einer früheren Endzeit endet das Intervall am Folgetag. Gleiche Uhrzeiten sind kein 24-Stunden-Intervall. Feiertagsregeln werden hier nicht abgeleitet.</p>
@endif
@if($group === 'shop')
<div class="field"><label for="fuel-types">Kraftstoffarten – eine je Zeile</label><textarea id="fuel-types" name="profile[fuel_types]" rows="4" maxlength="3030">{{ is_array(old('profile.fuel_types',$profileData['fuel_types'] ?? [])) ? implode("\n",old('profile.fuel_types',$profileData['fuel_types'] ?? [])) : old('profile.fuel_types') }}</textarea><small>Zum Beispiel Super (E5), E10, Diesel, LPG / Autogas, AdBlue oder Elektro-Laden.</small></div>
@foreach(\App\Stations\StationFields::choices() as $choiceKey=>$choices)<fieldset class="choice-group"><legend>{{ $choiceKey === 'services' ? 'Shop-Services' : 'Zusatzgeschäfte' }}</legend><div class="choice-grid">@foreach($choices as $value=>$label)<label class="check"><input type="checkbox" name="profile[{{ $choiceKey }}][]" value="{{ $value }}" @checked(in_array($value,old('profile.'.$choiceKey,$profileData[$choiceKey] ?? []) ?? [],true))>{{ $label }}</label>@endforeach</div></fieldset>@endforeach
@endif
@if($group === 'competition')
<p><a class="button secondary" href="{{ route('stations.competition',[$partner->id,$station->id]) }}">Wettbewerber aus Umkreissuche auswählen</a></p><p class="field-help">Vor dem Seitenwechsel ungespeicherte Änderungen sichern. Quell-Wettbewerber werden separat verwaltet.</p>
@foreach($sourceCompetitors as $entry)<div class="provider-preview"><strong>{{ $entry->snapshot['data']['name'] }}</strong><p>{{ $entry->snapshot['data']['street'] }} · {{ $entry->snapshot['data']['postal_code'] }} {{ $entry->snapshot['data']['city'] }}</p><p>@foreach(['e5'=>'E5','e10'=>'E10','diesel'=>'Diesel'] as $fuel=>$fuelLabel){{ $fuelLabel }}: {{ $entry->snapshot['data']['prices'][$fuel] ?? '–' }} {{ isset($entry->snapshot['data']['prices'][$fuel]) ? '€/l' : '' }}@if($entry->snapshot['data']['prices_last_known'][$fuel] ?? false) (zuletzt bekannt)@endif @endforeach</p><small>Benzinpreis-Aktuell.de · Abruf {{ \Carbon\Carbon::parse($entry->snapshot['fetched_at'])->timezone('Europe/Berlin')->format('d.m.Y H:i') }}</small></div>@endforeach

<p class="field-help">Preise in EUR pro Liter mit bis zu drei Nachkommastellen, zum Beispiel 1.859. Ein leerer Preis ist unbekannt. Ihre manuellen Angaben erhalten keinen vorgetäuschten Providerzeitstempel.</p>
@php $competitors = old('profile.competitors',$profileData['competitors'] ?? []); @endphp
<div data-repeat-list="competitors" data-repeat-limit="8">
@for($index=0;$index < min(8,max(count($competitors),1));$index++)
@include('stations.competitor',['row'=>$competitors[$index] ?? [],'index'=>$index])
@endfor
</div><button class="button secondary" type="button" data-add-competitor hidden>Wettbewerbsstandort hinzufügen</button><p class="field-help">Reihenfolge entspricht der Priorität. Ohne JavaScript lassen sich vorhandene Einträge und eine freie Zeile bearbeiten.</p>
@endif

</fieldset>
@if($group === 'map')@include('stations.map')@endif
@endif
</section>
@endforeach
@if($canWrite)<div class="details-actions"><p>Änderungen gelten für diese Station. Die Stationsanzahl bleibt auch bei „Standort aktiv: Nein“ unverändert.</p><button class="button primary" type="submit">Stationsdaten speichern</button></div>@endif
</form>
</div>
</div>
<script src="{{ asset('js/nova-station-details.js') }}" defer></script>
@endsection

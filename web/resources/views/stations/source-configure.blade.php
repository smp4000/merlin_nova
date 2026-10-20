@extends('layouts.partner')
@section('title', 'Benzinpreisanbindung')
@section('content')
<div class="page-heading"><div><p class="eyebrow">{{ $station->name }}</p><h1>Benzinpreisanbindung</h1><p class="page-subtitle">Quelle auswählen und prüfen. Ihre bestehenden Stammdaten werden nicht überschrieben.</p></div><a class="button secondary" href="{{ route('stations.details', [$partner->id, $station->id]) }}">Zurück zu den Stammdaten</a></div>
@if(session('source_message'))<p role="alert">{{ session('source_message') }}</p>@endif
<div class="wizard-panel"><div class="wizard-content">
<form method="post" class="station-form">@csrf
    <div class="station-form-grid"><div class="field"><label for="source-postal-code">Postleitzahl für die Suche</label><input id="source-postal-code" name="postal_code" maxlength="5" inputmode="numeric" value="{{ old('postal_code', $draft['query']['postal_code'] ?? $station->postal_code) }}"></div><div class="field"><label for="source-city">Ort für die Suche</label><input id="source-city" name="city" maxlength="100" value="{{ old('city', $draft['query']['city'] ?? $station->city) }}"></div></div>
    <p>Suche im Umkreis von 5 km in Deutschland. Beim Suchen werden PLZ und Ort an Benzinpreis-Aktuell.de gesendet.</p>
    <button class="button secondary" name="action" value="search">Stationen suchen</button>
    @include('stations.postal-lookup', ['postalInput' => 'source-postal-code', 'cityInput' => 'source-city'])
    @if(($draft['result']['status'] ?? '') === 'ok')
        <fieldset class="source-hits"><legend>Station auswählen</legend>@foreach($draft['result']['data'] as $hit)<label class="check"><input type="radio" name="source_id" value="{{ $hit['source_id'] }}" @checked(($draft['preview']['data']['source_id'] ?? '') === $hit['source_id'])><span><strong>{{ $hit['name'] }}</strong><br>{{ $hit['street'] ?? '' }}</span></label>@endforeach</fieldset>
        <button class="button secondary" name="action" value="preview">Auswahl prüfen</button>
        <p><small>Quelle: <a href="{{ $draft['result']['source_url'] }}" target="_blank" rel="noopener noreferrer">Benzinpreis-Aktuell.de</a> · Abruf: {{ \Carbon\Carbon::parse($draft['result']['fetched_at'])->timezone('Europe/Berlin')->format('d.m.Y H:i') }}</small></p>
    @endif
    @if(isset($draft['preview']))
        @include('stations.source-preview', ['source' => $draft['preview']])
        <p>Bitte prüfen Sie den Standort sorgfältig. Ein Suchtreffer bestätigt keine Betreiberberechtigung.</p>
        <label class="check"><input type="checkbox" name="confirmed" value="1">Ich habe die Zuordnung geprüft und möchte diese Quelle verknüpfen.</label>
        <button class="button primary" name="action" value="link">Anbindung speichern</button>
    @endif
</form>
</div></div>
@endsection

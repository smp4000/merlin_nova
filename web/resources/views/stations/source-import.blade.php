@extends('layouts.partner')
@section('title', 'Quelldaten prüfen')
@section('content')
<div class="page-heading"><div><p class="eyebrow">{{ $station->name }}</p><h1>Quelldaten prüfen und ergänzen</h1><p class="page-subtitle">Vorhandene manuelle Angaben werden nicht überschrieben.</p></div><a class="button secondary" href="{{ route('stations.details', [$partner->id, $station->id]) }}">Zurück zu den Stammdaten</a></div>
<div class="wizard-panel"><div class="wizard-content">
@include('stations.source-preview', ['expanded' => true])
<h2>Diese Leerstellen können ergänzt werden</h2>
<p>Öffnungszeiten: {{ count($additions['opening_hours'] ?? []) }} bisher unbelegte Wochentage. Koordinaten: {{ isset($additions['latitude']) ? 'leeres Koordinatenpaar ergänzbar' : 'nicht ergänzbar – vorhandene oder fehlende Angaben' }}.</p>
@if(isset($additions['opening_hours']))<p>{{ implode(', ', array_intersect_key(\App\Stations\StationFields::DAYS, $additions['opening_hours'])) }}</p>@endif
<form method="post" class="station-form">@csrf
<input type="hidden" name="version" value="{{ $version }}"><input type="hidden" name="source_snapshot" value="{{ $snapshot }}">
@include('stations.source-import-choices')
<label class="check"><input type="checkbox" name="confirmed" value="1" required>Ich habe Quelle und Angaben geprüft und bestätige die ausgewählte Ergänzung.</label>
<button class="button primary">Ausgewählte Angaben ergänzen</button>
</form></div></div>
@endsection

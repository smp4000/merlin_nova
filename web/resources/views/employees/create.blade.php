@extends('layouts.partner')
@section('title', 'Mitarbeiter anlegen')
@section('content')
<div class="employee-workspace">
<div class="page-heading"><div><p class="eyebrow">Mitarbeiter · Schritt 1</p><h1>Mitarbeiter vorbereiten</h1><p class="page-subtitle">Zuerst Name, Arbeitsstation und Fragebogen wählen. Anschließend bewusst einladen.</p></div><a href="{{ route('employees.index', $partner->id) }}" class="button secondary">Zur Übersicht</a></div>
@include('employees.errors')
<form class="employee-card station-form" method="post" action="{{ route('employees.create', $partner->id) }}">@csrf<input type="hidden" name="creation_key" value="{{ $creationKey }}">
<div class="station-form-grid">
<div class="field"><label for="first-name">Vorname</label><input id="first-name" name="first_name" required maxlength="80" autocomplete="off"></div>
<div class="field"><label for="last-name">Familienname</label><input id="last-name" name="last_name" required maxlength="80" autocomplete="off"></div>
<div class="field"><label for="employee-email">E-Mail für die Einladung</label><input id="employee-email" name="email" type="email" required maxlength="160" autocomplete="off"><small>Empfänger vor dem Versand prüfen. Keine echten Personendaten für lokale Tests.</small></div>
<div class="field"><label for="station">Stammstation</label><select id="station" name="station_id" required><option value="">Bitte wählen</option>@foreach($stations as $station)<option value="{{ $station->id }}">{{ $station->name }}</option>@endforeach</select></div>
<div class="field"><label for="profile">Personalfragebogen</label><select id="profile" name="profile" required>@foreach(\App\Employees\EmployeeFields::PROFILES as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select><small>Der Inhaber kann die Fallgruppe später in der Bearbeitung wechseln. Abrechnungseinstufung separat prüfen.</small></div>
</div>
<div class="info-banner"><span aria-hidden="true">i</span><p>Arbeitgeber: {{ $partner->name }}. Verwenden Sie diesen Partnerbereich für genau einen rechtlichen Arbeitgeber. Eine Einladung erteilt keine Verwaltungsrechte.</p></div>
<div class="wizard-actions"><a class="button secondary" href="{{ route('employees.index', $partner->id) }}">Abbrechen</a><button class="button primary">Mitarbeiter vorbereiten</button></div>
</form></div>
@endsection

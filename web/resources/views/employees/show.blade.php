@extends('layouts.partner')
@section('title', 'Mitarbeiterakte')
@section('content')
<div class="employee-workspace">
<div class="page-heading"><div><p class="eyebrow">Mitarbeiter · {{ \App\Employees\EmployeeFields::STATES[$employee->status] }}</p><h1>{{ $employee->identity['first_name'] }} {{ $employee->identity['last_name'] }}</h1><p class="page-subtitle">{{ \App\Employees\EmployeeFields::PROFILES[$employee->profile] }} · Fassung {{ $employee->version }}</p></div><a class="button secondary" href="{{ route('employees.index', $partner->id) }}">Zur Mitarbeiterübersicht</a></div>
@can('update',$employee)<div class="employee-buttons"><a class="button primary" href="{{ route('employees.edit',[$partner->id,$employee->uuid]) }}">Mitarbeiter bearbeiten</a><a class="button secondary" href="{{ route('employees.edit',[$partner->id,$employee->uuid]).'#delete' }}">Mitarbeiter löschen</a></div>@endcan
<p><a class="button secondary" href="{{ route('employees.documents',[$partner->id,$employee->uuid]) }}">Beschäftigungsnachweise verwalten</a></p>
@include('employees.errors')
@if($employee->status !== 'approved')
<section class="employee-card employee-invitation"><h2>E-Mail-Einladung</h2><p>Empfänger: <strong>{{ $employee->identity['email'] }}</strong></p>
@if($invitation)<p>Versand-/Zugangsstatus: {{ ['sending'=>'Versand läuft','ready'=>'Versendet / noch nicht aktiviert','active'=>'Aktiviert','submitted'=>'Eingereicht','changes_requested'=>'Rückfrage offen','failed'=>'Versand fehlgeschlagen','revoked'=>'Widerrufen','approved'=>'Geprüft'][$invitation->status] ?? 'Nicht verfügbar' }} · gültig bis {{ $invitation->expires_at->timezone('Europe/Berlin')->format('d.m.Y H:i') }} Uhr</p>@endif
@if($invitation?->status === 'failed')<p class="employee-delivery-error" role="alert">{{ app(\App\Support\RegistrationMail::class)->deliveryFailureMessage($invitation->failure_code) }} Der fehlgeschlagene Link ist gesperrt. Die Akte und der Entwurf bleiben erhalten.</p>@endif
@can('update', $employee)
@if($employee->status !== 'submitted')<form data-invitation-send method="post" action="{{ route('employees.invite', [$partner->id, $employee->uuid]) }}">@csrf<input type="hidden" name="version" value="{{ $employee->version }}"><label class="check"><input type="checkbox" name="confirmed" value="1" required>Empfänger geprüft. Einladung senden und vorherige Links/Sitzungen widerrufen. {{ $employee->status === 'managed' ? 'Die neue Selbstauskunft beginnt mit den aktuellen Inhaberstammdaten.' : 'Ein vorhandener Entwurf bleibt für diesen Empfänger erhalten.' }}</label><button class="button primary">{{ $invitation ? 'Erneut per E-Mail einladen' : 'Per E-Mail einladen' }}</button></form>@endif
@endcan
@if($invitation && !in_array($invitation->status, ['revoked','failed']))<form class="employee-revoke" method="post" action="{{ route('employees.revoke', [$partner->id, $employee->uuid]) }}">@csrf<button class="button secondary">Zugang widerrufen</button></form>@endif
<p class="field-help">Sieben Tage gültig. Aktivierung einmalig je Einladung; Wiederaufnahme im selben Browser. Kein Zugriff auf weitere Mitarbeiter oder Stationsverwaltung.</p></section>
@endif
@if($employee->status === 'managed' || ($invitation && $invitation->submitted_data))
<section class="employee-card"><h2>{{ $employee->status === 'managed' ? 'Vom Inhaber gepflegte Stammdaten' : ($employee->status === 'approved' ? 'Bestätigte Selbstangaben' : 'Selbstangaben zur Prüfung') }}</h2><p>{{ $employee->status === 'managed' ? 'Diese Fassung wurde vom Inhaber gepflegt. Bisherige Einladungszugänge sind gesperrt.' : ($employee->status === 'approved' ? 'Diese persönlichen Angaben wurden durch den Arbeitgeber bestätigt.' : ($invitation->status === 'submitted' ? 'Diese Fassung ist eingereicht. Persönliche Werte können Sie zurückfragen und anschließend bestätigen.' : 'Zuletzt eingereichte Fassung. Noch nicht eingereichte Änderungen des Mitarbeiters bleiben privat.')) }}</p>
<fieldset class="station-fields" disabled>@include('employees.fields', ['sections' => \App\Employees\EmployeeFields::personal($employee->profile), 'repeats' => \App\Employees\EmployeeFields::repeats('personal', $employee->profile), 'values' => in_array($employee->status,['approved','managed']) ? $employee->personal_data : $invitation->submitted_data, 'inputPrefix' => 'review-personal', 'readOnly' => true])</fieldset>
</section>
@endif
<form method="post" class="station-form" action="{{ route('employees.update', [$partner->id, $employee->uuid]) }}">@csrf
<input type="hidden" name="version" value="{{ $employee->version }}"><input type="hidden" name="submission_version" value="{{ $invitation?->version }}">
<div class="employee-card"><h2>Arbeitgeberangaben</h2><p class="field-help">Vertrag und Abrechnungseinordnung bleiben Arbeitgeberaufgabe. Kanzleifragen dürfen offen bleiben; eine Personalprüfung ist keine DATEV- oder Abrechnungsfreigabe.</p>
<fieldset class="station-fields" @cannot('update', $employee) disabled @endcannot @if($employee->status === 'approved') disabled @endif>
<div class="station-form-grid">
<div class="field"><label for="personnel-number">Personalnummer (vor Freigabe erforderlich)</label><input id="personnel-number" name="personnel_number" maxlength="20" value="{{ $personnelValue ?? $employee->personnel_number }}"><small>Eindeutig je Partner; führende Nullen bleiben erhalten.</small></div>
<div class="field"><label for="employer-station">Stammstation</label><select id="employer-station" name="station_id">@foreach($stations as $station)<option value="{{ $station->id }}" @selected($employee->station_id === $station->id)>{{ $station->name }}</option>@endforeach</select></div>
</div>
@include('employees.fields', ['sections' => \App\Employees\EmployeeFields::employer($employee->profile), 'repeats' => \App\Employees\EmployeeFields::repeats('employer', $employee->profile), 'values' => $employerValues ?? $employee->employer_data ?? [], 'inputPrefix' => 'employer', 'readOnly' => $employee->status === 'approved'])
</fieldset></div>
@can('update', $employee)@if($employee->status !== 'approved')
<div class="employee-card employee-actions">
@if($employee->status === 'submitted')
<h2>Angaben prüfen</h2><div class="field"><label for="review-question">Rückfrage (keine Steuer-/Bankwerte in den Text kopieren)</label><textarea id="review-question" name="question" maxlength="1500" rows="3">{{ $questionValue ?? '' }}</textarea></div>
<label class="check"><input type="checkbox" name="confirmed" value="1">Ich habe die eingereichten Angaben und Arbeitgeberdaten geprüft und bestätige diese Fassung. Offene Kanzleifragen sind damit nicht als geklärt erklärt.</label>
<div class="employee-buttons"><button class="button secondary" name="action" value="return">Zur Nachbesserung zurückgeben</button><button class="button primary" name="action" value="approve">Geprüfte Fassung bestätigen</button></div>
@endif
<button class="button secondary" name="action" value="save">Arbeitgeberangaben speichern</button>
</div>@endif@endcan
</form>
@if($reviews->isNotEmpty())<details class="employee-section"><summary>Prüfverlauf</summary><div class="employee-section-content"><ul>@foreach($reviews as $review)<li>{{ $review->created_at->timezone('Europe/Berlin')->format('d.m.Y H:i') }} · Fassung {{ $review->employee_version }} · {{ ['save'=>'Arbeitgeberangaben gespeichert','return'=>'Rückfrage','edit'=>'Stammdaten durch Inhaber bearbeitet','trash'=>'In Papierkorb verschoben','restore'=>'Wiederhergestellt','approve'=>'Geprüft und bestätigt'][$review->action] }}</li>@endforeach</ul><p class="field-help">Freigegebene Fassungen sind verschlüsselt gespeichert. Es wurde nichts an DATEV übermittelt.</p></div></details>@endif
</div>
<script src="{{ asset('js/nova-employees.js') }}" defer></script>
@endsection

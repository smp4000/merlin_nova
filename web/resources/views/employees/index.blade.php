@extends('layouts.partner')
@section('title', 'Mitarbeiter')
@section('content')
<div class="employee-workspace">
<div class="page-heading"><div><p class="eyebrow">Ihr Team</p><h1>Mitarbeiter</h1><p class="page-subtitle">Anlage, Selbstauskunft und Arbeitgeberprüfung.</p></div>@can('create', [\App\Models\Employee::class, $partner])<a class="button primary" href="{{ route('employees.create', $partner->id) }}">Mitarbeiter anlegen</a>@endcan</div>
<p><a href="{{ route('employees.choices',$partner->id) }}">Auswahllisten ergänzen</a> · <a href="{{ route('employees.trash.index',$partner->id) }}">Papierkorb öffnen</a></p>
<div class="station-list-panel">
@forelse($employees as $employee)
<article class="partner-station-row"><div class="station-description"><h3>{{ $employee->identity['first_name'] }} {{ $employee->identity['last_name'] }}</h3><p>{{ $stations[$employee->station_id] ?? 'Station' }} · {{ \App\Employees\EmployeeFields::PROFILES[$employee->profile] }}</p><small>{{ \App\Employees\EmployeeFields::STATES[$employee->status] }}</small></div><div class="employee-row-actions">@can('update',$employee)<a class="button secondary" href="{{ route('employees.edit',[$partner->id,$employee->uuid]) }}">Bearbeiten</a><a class="button secondary" href="{{ route('employees.edit',[$partner->id,$employee->uuid]).'#delete' }}">Löschen</a>@endcan<a class="station-detail-link" href="{{ route('employees.show', [$partner->id, $employee->uuid]) }}">Akte öffnen →</a></div></article>
@empty<div class="empty-stations"><h2>Der erste Mitarbeiter</h2><p>Bereiten Sie den Zugang vor. Der Mitarbeiter ergänzt seine Angaben nach Ihrer E-Mail-Einladung.</p></div>@endforelse
{{ $employees->links() }}
</div><p class="field-help">Personalakten sind in diesem Paket ausschließlich für Inhaber freigegeben. Arbeitsstation und Benutzerrechte bleiben getrennt.</p>
</div>
@endsection

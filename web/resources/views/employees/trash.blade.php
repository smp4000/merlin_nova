@extends('layouts.partner')
@section('title','Mitarbeiter-Papierkorb')
@section('content')
<div class="employee-workspace"><div class="page-heading"><div><p class="eyebrow">Mitarbeiter</p><h1>Papierkorb</h1><p class="page-subtitle">Ausgeblendete Akten und Prüffassungen bleiben gespeichert. Alte Einladungszugänge bleiben gesperrt.</p></div><a class="button secondary" href="{{ route('employees.index',$partner->id) }}">Aktive Mitarbeiter</a></div>
@forelse($employees as $employee)<article class="employee-card"><h2>{{ $employee->identity['first_name'] }} {{ $employee->identity['last_name'] }}</h2><p>Gelöscht am {{ $employee->deleted_at->timezone('Europe/Berlin')->format('d.m.Y H:i') }} Uhr · Personalnummer {{ $employee->personnel_number ?? 'nicht vergeben' }}</p>@can('restore',$employee)<form method="post" action="{{ route('employees.restore',[$partner->id,$employee->uuid]) }}">@csrf<input type="hidden" name="version" value="{{ $employee->version }}"><button class="button secondary">Mitarbeiter wiederherstellen</button></form>@endcan</article>@empty<div class="employee-card"><p>Der Papierkorb ist leer.</p></div>@endforelse
{{ $employees->links() }}</div>
@endsection

@extends('employees.public.layout')
@section('content')
<div class="employee-card employee-start"><p class="eyebrow">Willkommen</p><h1>Ihre Angaben für den Einstieg</h1><p>Füllen Sie die Selbstauskunft für Ihren Arbeitgeber aus. Sie können zwischenspeichern und später in diesem Browser fortsetzen.</p><p>Erst Ihre Einreichung startet die Arbeitgeberprüfung. Dieser Zugang erteilt keine Stations- oder Verwaltungsrechte.</p>
<form method="post" action="{{ route('employees.accept', $uuid) }}" data-invitation-accept>@csrf<div class="field"><label for="invitation-token">Persönlicher Zugangsschlüssel aus der Einladung</label><input id="invitation-token" name="token" type="password" minlength="64" maxlength="64" autocomplete="off" required><small>Wird aus dem Einladungslink übernommen. Ohne JavaScript: die Zeichen nach # aus dem Link hier einfügen.</small></div><button class="button primary">Selbstauskunft aktivieren</button></form>
<p class="field-help">Persönlicher Link, sieben Tage gültig. Aktivierung einmalig; danach denselben Browser verwenden. Bei abgelaufenem oder verlorenem Zugang den Arbeitgeber um eine neue Einladung bitten.</p></div>
@endsection

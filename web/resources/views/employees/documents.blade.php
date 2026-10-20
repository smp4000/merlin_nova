@extends($invitation ? 'employees.public.layout' : 'layouts.partner')
@section('title', 'Beschäftigungsnachweise')
@section('content')
<div class="employee-workspace">
<div class="page-heading"><div><p class="eyebrow">Personal · Geschützte Unterlagen</p><h1>Beschäftigungsnachweise</h1><p>Allgemeine Beschäftigungsnachweise als Foto oder PDF. Keine Gesundheitsunterlagen hochladen.</p></div><a class="button secondary" href="{{ $back }}">Zurück zur Akte</a></div>
@if($documentErrors)<div class="employee-card" role="alert">@foreach($documentErrors as $messages)@foreach($messages as $message)<p>{{ $message }}</p>@endforeach@endforeach</div>@endif
<section class="employee-card"><h2>Ihre Unterlagen</h2>
<p class="field-help">{{ $invitation ? 'Entwürfe bleiben in diesem Zugang privat. Erst beim Einreichen Ihrer Selbstauskunft sieht der Arbeitgeber die Nachweise.' : 'Sichtbar sind eigene Nachweise und ausdrücklich eingereichte Mitarbeiterunterlagen. Private Entwürfe bleiben verborgen.' }}</p>
@forelse($documents as $document)
<div class="employee-document-row"><div><a href="{{ $base.'/'.$document->uuid }}">{{ $document->original_name }}</a><small>{{ number_format($document->bytes/1024,0,',','.') }} KB · {{ $document->created_at->format('d.m.Y H:i') }} · {{ $document->invitation_id ? ($document->submitted_at ? 'Eingereicht' : 'Privater Entwurf') : 'Arbeitgeberablage' }}</small></div>
@if($writable && (!$invitation || !$document->submitted_at))<form method="post" action="{{ $base.'/'.$document->uuid }}">@csrf<label class="check"><input type="checkbox" name="confirmed" value="1" required>Entfernen bestätigen</label><button class="button secondary">Entfernen</button></form>@endif</div>
@empty<p>Noch keine sichtbaren Nachweise.</p>@endforelse
</section>
@if($writable)<section class="employee-card"><h2>Nachweis hinzufügen</h2><p>JPEG, PNG oder PDF, höchstens 2 MiB und 4 Megapixel je Bild. Maximal 20 Dateien einschließlich entfernter Fassungen. Fotos werden ohne gespeicherte Kamerametadaten neu erzeugt.</p>
<form method="post" enctype="multipart/form-data" action="{{ $base }}">@csrf
<div class="field"><label for="employee-document">Datei auswählen</label><input id="employee-document" type="file" name="document" accept="image/jpeg,image/png,application/pdf" required></div>
<label class="check"><input type="checkbox" name="confirmed" value="1" required>Dies ist ein allgemeiner Beschäftigungsnachweis und enthält keine Gesundheitsunterlagen.</label><button class="button primary">Datei geschützt hochladen</button></form>
<details><summary>Foto mit dem Handy aufnehmen</summary><p>Auf dem Handy öffnet die Fotoauswahl je nach Browser auch die Kamera. Nutzen Sie alternativ die folgende Kameraauswahl.</p><form method="post" enctype="multipart/form-data" action="{{ $base }}">@csrf<div class="field"><label for="employee-camera">Foto aufnehmen</label><input id="employee-camera" type="file" name="document" accept="image/jpeg,image/png" capture="environment" required></div><label class="check"><input type="checkbox" name="confirmed" value="1" required>Allgemeiner Beschäftigungsnachweis, keine Gesundheitsunterlagen.</label><button class="button primary">Foto geschützt hochladen</button></form></details>
</section>@endif
<section class="employee-card"><h2>Handy-Übergabe per QR-Code</h2><p>Vorbereitet: Für die Übergabe vom PC an das Handy wird eine freigegebene HTTPS-Adresse benötigt. Der lokale Rechnerzugang ist vom Handy aus nicht erreichbar. Deshalb ist die QR-Übergabe derzeit gesperrt.</p><button class="button secondary" type="button" disabled>QR-Code nach HTTPS-Freigabe verfügbar</button></section>
<p class="field-help">Keine vollständige Schadsoftwareprüfung. Dateien nur aus vertrauenswürdiger Quelle öffnen. Eine automatische Aufbewahrungs- oder Löschfrist ist noch nicht eingerichtet.</p>
</div>
@endsection

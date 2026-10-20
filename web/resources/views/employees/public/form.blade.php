@extends('employees.public.layout')
@section('content')
<div class="page-heading"><div><p class="eyebrow">{{ \App\Employees\EmployeeFields::PROFILES[$profile] }}</p><h1>Ihre Selbstauskunft</h1><p class="page-subtitle">Persönliche Angaben ergänzen, prüfen und einreichen.</p></div></div>
@include('employees.errors')
@if($invitation->question)<div class="employee-card"><h2>Rückfrage Ihres Arbeitgebers</h2><p class="employee-question">{{ $invitation->question }}</p></div>@endif
<div class="info-banner"><span aria-hidden="true">i</span><div><p>Zur Prüfung durch {{ $partnerName }}. * Pflicht vor dem Einreichen.</p><details><summary>Datenschutz und Hinweise zur Erfassung</summary><p>Unbekannte Steuer-/SV-Nummern können Sie mit Ihrem Arbeitgeber klären.</p><p>Ihr einladender Arbeitgeber {{ $partnerName }} ist für die Personalerfassung verantwortlich. Die Angaben dienen der Vorbereitung und Prüfung Ihrer Beschäftigung. Kontakt und erforderliche Nachweise vor der Eingabe bei Bedarf mit ihm klären.</p><p>Nur Ihre eigene Selbstauskunft ist zugänglich. Arbeitgeberfelder wie Vergütung und Abrechnungsschlüssel pflegt die Personalverwaltung. Angaben zu Kindern nur nach Aufforderung erfassen. Keine Gesundheitsunterlagen hochladen; Dokumentuploads sind hier nicht vorgesehen.</p></details></div></div>
@if(!$writable)<p class="local-warning">{{ $invitation->status === 'submitted' ? 'Ihre Angaben sind eingereicht und warten auf Prüfung. Sie können diese Fassung bis zum Ablauf ansehen.' : 'Der Zugang ist derzeit nur lesend.' }}</p>@endif
@php
    // Reine Anzeigegruppen: Fachregeln und erlaubte Felder bleiben im gemeinsamen
    // serverseitigen Katalog. Verstecken eines Schritts verleiht keine Rechte.
    $personalSections = \App\Employees\EmployeeFields::personal($profile);
    $personalRepeats = \App\Employees\EmployeeFields::repeats('personal', $profile);
    $wizardSteps = [
        ['label'=>'Person', 'title'=>'Person & Anschrift', 'sections'=>['person'], 'repeats'=>[]],
        ['label'=>'Steuer & Versicherung', 'title'=>'Steuer & Versicherung', 'sections'=>['insurance'], 'repeats'=>[]],
        ['label'=>'Gehaltskonto', 'title'=>'Ihr Gehaltskonto', 'sections'=>['bank'], 'repeats'=>[]],
        ['label'=>'Beschäftigungen', 'title'=>'Weitere und frühere Beschäftigungen', 'sections'=>['jobs'], 'repeats'=>['other_jobs','previous_jobs']],
        ['label'=>'Ergänzungen', 'title'=>'Weitere Angaben, soweit zutreffend', 'sections'=>['vwl','children_info'], 'repeats'=>['children']],
        ['label'=>'Prüfen', 'title'=>'Angaben prüfen und einreichen', 'sections'=>[], 'repeats'=>[]],
    ];
@endphp
<form method="post" class="station-form employee-wizard" data-employee-wizard data-wizard-start="{{ (int) session('employee_wizard_step', 0) }}" action="{{ route('employees.self.save', $invitation->uuid) }}">@csrf<input type="hidden" name="version" value="{{ $invitation->version }}">
<input type="hidden" name="wizard_step" value="0" data-wizard-position>
<nav class="employee-step-navigation" aria-label="Schritte der Selbstauskunft" data-wizard-navigation hidden>
<ol>@foreach($wizardSteps as $step)<li><button type="button" data-wizard-go="{{ $loop->index }}"><span>{{ $loop->iteration }}</span>{{ $step['label'] }}</button></li>@endforeach</ol>
</nav>
<p class="employee-step-progress" data-wizard-progress role="status" hidden></p>
@foreach($wizardSteps as $step)
@php
    $stepKeys = array_merge($step['sections'], $step['repeats']);
    $stepHasErrors = collect(array_keys($fieldErrors ?? []))->contains(fn($key) => in_array(explode('.', $key)[1] ?? '', $stepKeys, true) || ($loop->last && $key === 'confirmed'));
@endphp
<section class="employee-wizard-step" data-wizard-step data-wizard-label="{{ $step['label'] }}" @if($stepHasErrors) data-wizard-error @endif aria-labelledby="self-step-{{ $loop->index }}">
<h2 id="self-step-{{ $loop->index }}" tabindex="-1">{{ $step['title'] }}</h2>
@if(!$loop->last)
<fieldset class="station-fields" @if(!$writable) disabled @endif>
@include('employees.fields', ['sections'=>array_intersect_key($personalSections,array_flip($step['sections'])), 'repeats'=>array_intersect_key($personalRepeats,array_flip($step['repeats'])), 'values'=>$data, 'inputPrefix'=>'self', 'readOnly'=>!$writable, 'wizardMode'=>true])
</fieldset>
@else
<p>{{ $writable ? 'Bitte prüfen Sie Ihre Angaben. Über die Schritte können Sie Korrekturen vornehmen.' : 'Hier sehen Sie Ihre eingereichten Angaben. Änderungen sind derzeit gesperrt.' }} Unbekannte Angaben klären Sie mit Ihrem Arbeitgeber.</p>
<div data-wizard-review></div>
<noscript><p>Bitte prüfen Sie vor dem Einreichen die oben aufgeführten Abschnitte.</p></noscript>
@if($writable)<div class="employee-card employee-actions"><label class="check"><input type="checkbox" name="confirmed" value="1">Ich habe meine Angaben geprüft und reiche sie zur Arbeitgeberprüfung ein. Das ist keine Vertragsunterschrift.</label><button class="button primary" name="action" value="submit">Zur Prüfung einreichen</button></div>@endif
@endif
</section>
@endforeach
<div class="employee-wizard-actions">
<button type="button" class="button secondary" data-wizard-back hidden>Zurück</button>
@if($writable)<button class="button secondary" name="action" value="save">Entwurf speichern</button>@endif
<button type="button" class="button primary" data-wizard-next hidden>Weiter</button>
</div>
@if($writable)<p class="field-help">„Weiter“ wechselt nur den Schritt. Erst „Entwurf speichern“ oder „Zur Prüfung einreichen“ speichert nach erfolgreicher Rückmeldung. Kein automatischer Offline-Speicher.</p>@endif
</form>
<section class="employee-card"><h2>Beschäftigungsnachweise</h2><p>Speichern Sie zuerst Ihren Entwurf. Auf der separaten Seite können Sie Fotos oder PDF-Dateien ergänzen.</p><a class="button secondary" href="{{ route('employees.self.documents', $invitation->uuid) }}">Unterlagen verwalten</a></section>
@endsection

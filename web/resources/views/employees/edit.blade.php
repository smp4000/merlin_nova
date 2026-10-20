@extends('layouts.partner')
@section('title','Mitarbeiter bearbeiten')
@section('content')
<div class="employee-workspace employee-editor" data-employee-editor>
<div class="page-heading"><div><p class="eyebrow">Mitarbeiter · Inhaberpflege</p><h1>Mitarbeiter bearbeiten</h1><p class="page-subtitle">Stammdaten und Arbeitgeberangaben selbst pflegen – auch nach der Prüfung.</p></div><a class="button secondary" href="{{ route('employees.show',[$partner->id,$employee->uuid]) }}">Zur Akte</a></div>
@include('employees.errors')
<div class="info-banner"><span aria-hidden="true">i</span><p>Speichern sperrt bisherige Einladungslinks und Sitzungen. Nicht eingereichte Mitarbeiterentwürfe bleiben privat und werden nicht in diese Maske übernommen. Ihre Änderung wird als Inhaberpflege dokumentiert; es wird keine E-Mail gesendet.</p></div>
<nav class="employee-editor-tabs" aria-label="Bereiche der Mitarbeiterbearbeitung" hidden data-editor-tabs>
@foreach(['person'=>'Person','insurance'=>'Steuer & Versicherung','bank'=>'Gehaltskonto','employment'=>'Beschäftigung','extras'=>'Ergänzungen','management'=>'Verwaltung'] as $tabKey=>$tabLabel)
<button type="button" id="editor-tab-{{ $tabKey }}" data-editor-tab="{{ $tabKey }}">{{ $tabLabel }}<span data-editor-error-count></span></button>
@endforeach
</nav>
<details class="employee-section" data-editor-panel="management"><summary>Personalfragebogen wechseln · {{ \App\Employees\EmployeeFields::PROFILES[$profile] }}</summary><div class="employee-section-content"><form method="get" action="{{ route('employees.edit',[$partner->id,$employee->uuid]) }}"><label for="edit-profile">Passenden Fragebogen anzeigen</label><select id="edit-profile" name="profile">@foreach(\App\Employees\EmployeeFields::PROFILES as $key=>$label)<option value="{{ $key }}" @selected($key===$profile)>{{ $label }}</option>@endforeach</select><button class="button secondary">Fragebogen anzeigen</button><p class="field-help">Der Wechsel lädt die Maske neu und verwirft ungespeicherte Eingaben. Erst „Änderungen speichern“ übernimmt die Fallgruppe; nicht mehr passende Angaben bleiben nur in vorhandenen Prüffassungen.</p></form></div></details>
<form method="post" class="station-form" action="{{ route('employees.edit',[$partner->id,$employee->uuid]) }}">@csrf
<input type="hidden" name="version" value="{{ $employee->version }}"><input type="hidden" name="profile" value="{{ $profile }}">
<section class="employee-card" data-editor-panel="management"><h2>Zuordnung & Einladungsadresse</h2><div class="station-form-grid">
<div class="field"><label for="edit-email">E-Mail für neue Einladungen</label><input id="edit-email" @if(!empty($fieldErrors['email'])) aria-invalid="true" @endif type="email" name="email" value="{{ $email }}" maxlength="160" required></div>
<div class="field"><label for="edit-number">Personalnummer</label><input id="edit-number" @if(!empty($fieldErrors['personnel_number'])) aria-invalid="true" @endif name="personnel_number" value="{{ $personnel_number }}" maxlength="20"><small>Führende Nullen bleiben erhalten. Auch der Papierkorb reserviert vergebene Nummern.</small></div>
<div class="field"><label for="edit-station">Stammstation</label><select id="edit-station" @if(!empty($fieldErrors['station_id'])) aria-invalid="true" @endif name="station_id">@foreach($stations as $station)<option value="{{ $station->id }}" @selected($station_id===$station->id)>{{ $station->name }}</option>@endforeach</select></div>
</div></section>
@php
$personalSections=\App\Employees\EmployeeFields::personal($profile);
$personalRepeats=\App\Employees\EmployeeFields::repeats('personal',$profile);
$editorGroups=['person'=>['person'],'insurance'=>['insurance'],'bank'=>['bank'],'extras'=>['jobs','vwl','children_info']];
@endphp
@foreach($editorGroups as $tabKey=>$sectionKeys)
@php
$groupKeys=$sectionKeys;
if($tabKey==='extras') $groupKeys=array_merge($groupKeys,array_keys($personalRepeats));
$tabHasErrors=collect(array_keys($fieldErrors ?? []))->contains(fn($error)=>collect($groupKeys)->contains(fn($key)=>$error==='personal_data.'.$key || str_starts_with($error,'personal_data.'.$key.'.')));
@endphp
<section @if($tabHasErrors) data-editor-error @endif data-editor-panel="{{ $tabKey }}" id="editor-panel-{{ $tabKey }}" aria-labelledby="editor-tab-{{ $tabKey }}">
@if($tabKey==='person')<p class="field-help">Vorname und Familienname sind erforderlich. Weitere Angaben können zunächst unvollständig bleiben.</p>@endif
@include('employees.fields',['sections'=>array_intersect_key($personalSections,array_flip($sectionKeys)),'repeats'=>$tabKey==='extras' ? $personalRepeats : [],'values'=>$personal_data,'inputPrefix'=>'owner-personal','fieldRoot'=>'personal_data','readOnly'=>false,'wizardMode'=>true])
</section>
@endforeach
<section @if(collect(array_keys($fieldErrors ?? []))->contains(fn($error)=>str_starts_with($error,'employer_data.'))) data-editor-error @endif data-editor-panel="employment" id="editor-panel-employment" aria-labelledby="editor-tab-employment">
@include('employees.fields',['sections'=>\App\Employees\EmployeeFields::employer($profile),'repeats'=>\App\Employees\EmployeeFields::repeats('employer',$profile),'values'=>$employer_data,'inputPrefix'=>'owner-employer','fieldRoot'=>'employer_data','readOnly'=>false,'wizardMode'=>true])
</section>
<section class="employee-card employee-editor-save"><div class="field"><label for="edit-reason">Änderungsgrund</label><textarea id="edit-reason" name="reason" rows="2" maxlength="500" required>{{ $reason }}</textarea><small>Kurz den Anlass nennen. Keine Bank-/Steuerwerte in den Text kopieren.</small></div><label class="check"><input type="checkbox" name="confirmed" value="1" required>Ich übernehme diese Angaben als Inhaber. Bisherige Einladungslinks und Mitarbeitersitzungen werden gesperrt.</label><div class="employee-buttons"><a class="button secondary" href="{{ route('employees.show',[$partner->id,$employee->uuid]) }}">Abbrechen</a><button class="button primary">Änderungen speichern</button></div></section>
</form>
<details id="delete" class="employee-section" data-editor-panel="management"><summary>Mitarbeiter löschen · Papierkorb</summary><div class="employee-section-content"><p>Die Akte verschwindet aus der aktiven Liste. Einladungszugänge werden gesperrt. Stammdaten und Prüfhistorie bleiben im Papierkorb erhalten und können wiederhergestellt werden. Keine endgültige Datenvernichtung.</p><form method="post" action="{{ route('employees.trash',[$partner->id,$employee->uuid]) }}">@csrf<input type="hidden" name="version" value="{{ $employee->version }}"><label class="check"><input type="checkbox" name="confirmed" value="1" required>Ich möchte diesen Mitarbeiter in den Papierkorb verschieben.</label><button class="button secondary">In den Papierkorb verschieben</button></form></div></details>
</div><script src="{{ asset('js/nova-employee-tabs.js') }}" defer></script><script src="{{ asset('js/nova-employees.js') }}" defer></script>
@endsection

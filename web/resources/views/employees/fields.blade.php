{{-- Einheitliche, serverseitig definierte Felder für Selbstangaben und Arbeitgeber.
     Keine dynamischen HTML-/Feldnamen aus Nutzereingaben. Wiederholungen bleiben
     auch ohne JavaScript mit den bereits vorhandenen Leerzeilen ausfüllbar.
     Bei Fehlern nur betroffene Gruppen öffnen: fremde Readonly-Abschnitte dürfen
     einen kleinen Arbeitgeberfehler nicht in seitenlanges Scrollen verwandeln. --}}
@php $fieldRoot = $fieldRoot ?? 'data'; @endphp
@foreach($sections as $sectionKey => $section)
@php $sectionHasErrors = collect(array_keys($fieldErrors ?? []))->contains(fn($key) => $key === $fieldRoot.'.'.$sectionKey || str_starts_with($key, $fieldRoot.'.'.$sectionKey.'.')); @endphp
<details class="employee-section" @if((($wizardMode ?? false) || ($loop->first && !($readOnly ?? false))) || $sectionHasErrors) open @endif>
    <summary>{{ $section['label'] }}</summary>
    <div class="employee-section-content"><div class="station-form-grid">
    @foreach($section['fields'] as $key => $field)
        @include('employees.input', ['fieldKey' => $sectionKey.'.'.$key, 'fieldName' => $fieldRoot.'['.$sectionKey.']['.$key.']', 'value' => data_get($values, $sectionKey.'.'.$key)])
    @endforeach
    </div>
    @if($sectionKey === 'bank' && !($readOnly ?? false))@include('employees.bank-tools')@endif
    </div>
</details>
@endforeach
@foreach($repeats as $groupKey => $group)
@php $groupHasErrors = collect(array_keys($fieldErrors ?? []))->contains(fn($key) => $key === $fieldRoot.'.'.$groupKey || str_starts_with($key, $fieldRoot.'.'.$groupKey.'.')); @endphp
<details class="employee-section" @if(!empty($values[$groupKey]) || $groupHasErrors) open @endif>
    <summary>{{ $group['label'] }}</summary>
    <div class="employee-section-content" data-repeat-group="{{ $groupKey }}">
    <p class="field-help">Nur ausfüllen, wenn der Sachverhalt zutrifft. Vollständig leere Zeilen werden nicht gespeichert. Maximal 20 Einträge.</p>
    <div data-repeat-rows>
    @php $rows = is_array($values[$groupKey] ?? null) ? array_values($values[$groupKey]) : []; if(empty($rows)) $rows = [[], []]; @endphp
    @foreach(array_slice($rows, 0, 20) as $rowIndex => $row)
        <fieldset class="employee-repeat-row" data-repeat-row><legend>Eintrag {{ $rowIndex + 1 }}</legend><div class="station-form-grid">
        @foreach($group['fields'] as $key => $field)
            @include('employees.input', ['fieldKey' => $groupKey.'.'.$rowIndex.'.'.$key, 'fieldName' => $fieldRoot.'['.$groupKey.']['.$rowIndex.']['.$key.']', 'value' => is_array($row) ? ($row[$key] ?? null) : null])
        @endforeach
        </div>@unless($readOnly ?? false)<button type="button" class="button secondary" data-remove-row>Eintrag leeren</button>@endunless</fieldset>
    @endforeach
    </div>
    @unless($readOnly ?? false)<button type="button" class="button secondary" data-add-row>Weiteren Eintrag hinzufügen</button>@endunless
    </div>
</details>
@endforeach

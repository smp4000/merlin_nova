@php
    $inputId = ($inputPrefix ?? 'employee').'-'.str_replace('.', '-', $fieldKey);
    $safeValue = is_scalar($value) ? (string) $value : '';
    $inputErrors = $fieldErrors[($fieldRoot ?? 'data').'.'.$fieldKey] ?? [];
    $inputType = match($field['type']) { 'birth', 'date' => 'date', 'month' => 'month', 'tel' => 'tel', default => 'text' };
@endphp
<div class="field">
<label for="{{ $inputId }}">{{ $field['label'] }}@if($field['required']) <span aria-hidden="true">*</span>@endif</label>
@if($field['type'] === 'select')
<select id="{{ $inputId }}" name="{{ $fieldName }}" @if(!empty($inputErrors)) aria-invalid="true" @endif><option value="">Nicht angegeben</option>@foreach($field['options'] as $optionKey => $label)<option value="{{ $optionKey }}" @selected($safeValue === (string) $optionKey)>{{ $label }}</option>@endforeach</select>
@else
<input id="{{ $inputId }}" name="{{ $fieldName }}" type="{{ $inputType }}" value="{{ $safeValue }}" maxlength="160" autocomplete="off" @if($field['type'] === 'suggestion') list="{{ $inputId }}-options" aria-describedby="{{ $inputId }}-choice-help" @endif @if($field['type'] === 'iban') data-iban-input data-iban-lengths='@json(\App\Rules\Iban::LENGTHS)' aria-describedby="{{ $inputId }}-iban-hint" spellcheck="false" autocapitalize="characters" @endif @if(!empty($inputErrors)) aria-invalid="true" @endif @if(in_array($field['type'], ['money','hours','daily_hours','days','factor','allowance'])) inputmode="decimal" @endif>
@endif
@if($field['type'] === 'suggestion')
<datalist id="{{ $inputId }}-options">@foreach(($choices[$field['category']] ?? \App\Employees\EmployeeChoices::base($field['category'])) as $suggestion)<option value="{{ $suggestion }}"></option>@endforeach</datalist>
<small id="{{ $inputId }}-choice-help">Auswahl suchen oder fehlenden Wert selbst eingeben.</small>
@endif
@if($field['type'] === 'iban')<small id="{{ $inputId }}-iban-hint" data-iban-hint role="status">Lokale Format- und Prüfzifferprüfung. Keine Konto- oder Inhaberbestätigung.</small>@endif
@if(!empty($inputErrors))<small class="employee-field-error">{{ implode(' ', $inputErrors) }}</small>@endif
</div>

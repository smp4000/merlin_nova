@php
    $fieldId = 'profile-'.str_replace('.', '-', $key);
    $value = old('profile.'.$key, data_get($profileData, $key));
    $fieldError = $errors->first($key) ?: $errors->first('profile.'.$key);
    $catalog = \App\Stations\StationCatalogs::fixed()[$key] ?? null;
    $suggestions = \App\Stations\StationCatalogs::suggestions()[$key] ?? [];
    $isLegacy = $catalog !== null && filled($value) && !array_key_exists((string)$value,$catalog)
        && (string)$value === (string)data_get($profileData,$key);
@endphp
@if($type !== 'legacy' || filled($value))
<div class="field {{ $type === 'textarea' ? 'field-wide' : '' }}" @if($fieldError) data-field-error @endif>
<label for="{{ $fieldId }}">{{ $label }}</label>
@if($catalog !== null)
<select id="{{ $fieldId }}" name="profile[{{ $key }}]" @if($fieldError) aria-invalid="true" @endif>
<option value="">Nicht angegeben</option>
@if(filled($value) && !array_key_exists((string)$value,$catalog))<option value="{{ $value }}" selected>{{ $isLegacy ? $value.' · bisheriger Wert' : 'Ungültige Auswahl – bitte erneut wählen' }}</option>@endif
@foreach($catalog as $code=>$choiceLabel)<option value="{{ $code }}" @selected((string)$value === (string)$code)>{{ $choiceLabel }}</option>@endforeach
</select>
@if($isLegacy)<small>Bestehender Eintrag bleibt erhalten. Sie können bewusst einen Listenwert auswählen.</small>@endif
@elseif($type === 'area')
<input id="{{ $fieldId }}" name="profile[{{ $key }}]" value="{{ $value }}" type="number" min="0" max="999999.9" step="0.1" inputmode="decimal" @if($fieldError) aria-invalid="true" @endif><small>Verkaufsfläche in m², höchstens eine Nachkommastelle.</small>
@elseif($type === 'legacy')
<input id="{{ $fieldId }}" name="profile[{{ $key }}]" value="{{ $value }}" readonly><small>Unveränderter Bestandswert. Bitte die neue Angabe separat prüfen und erfassen; keine automatische Übertragung.</small>
@elseif($type === 'boolean')
<select id="{{ $fieldId }}" name="profile[{{ $key }}]"><option value="">Nicht angegeben</option><option value="1" @selected($value !== null && (string) $value === '1')>Ja</option><option value="0" @selected($value !== null && in_array($value, [false, 0, '0'], true))>Nein</option></select>
@elseif($type === 'textarea')
<textarea id="{{ $fieldId }}" name="profile[{{ $key }}]" rows="3" maxlength="{{ $limit }}" @if($fieldError) aria-invalid="true" @endif>{{ $value }}</textarea>
@elseif($type === 'timezone')
<input id="{{ $fieldId }}" name="profile[{{ $key }}]" value="{{ $value }}" list="station-timezones" maxlength="100" placeholder="z. B. Europe/Berlin"><small>Die Zeitzone gehört zu diesem Standort.</small>
@elseif($suggestions)
<input id="{{ $fieldId }}" name="profile[{{ $key }}]" value="{{ $value }}" list="station-options-{{ $key }}" maxlength="{{ $limit }}" @if($fieldError) aria-invalid="true" @endif>
<small>Vorschlag auswählen oder eigenen Wert eingeben.</small>
@else
<input id="{{ $fieldId }}" name="profile[{{ $key }}]" value="{{ $value }}" type="{{ in_array($type, ['email', 'url', 'tel', 'date'], true) ? $type : ($type === 'integer' ? 'number' : 'text') }}" @if(in_array($type, ['price','dimension','latitude','longitude'],true)) inputmode="decimal" @endif @if($type === 'integer') min="1" max="10000" @else maxlength="{{ $limit }}" @endif @if($fieldError) aria-invalid="true" @endif>
@endif
@if($fieldError)<small class="field-error" role="alert">{{ $fieldError }}</small>@endif
</div>
@endif

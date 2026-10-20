<div class="postal-lookup" data-postal-lookup data-endpoint="{{ route('partner.postal-place', $partner->id) }}" data-postal-input="{{ $postalInput }}" data-city-input="{{ $cityInput }}" data-country-input="{{ $countryInput ?? '' }}">
    <button class="button secondary" type="button" data-lookup-place>Ort ermitteln</button>
    <p class="field-help">Nur nach Klick wird die vollständige deutsche PLZ übermittelt. Ein vorhandener Ort bleibt erhalten.</p>
    <p role="status" aria-live="polite" data-lookup-status></p>
    <label hidden data-place-choice-label>Ort auswählen <select data-place-choice></select></label>
    <small>Ortsdaten: © <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap-Mitwirkende</a> · <a href="https://operations.osmfoundation.org/policies/nominatim/" target="_blank" rel="noopener noreferrer">Nominatim</a> · ODbL. Keine Ermittlung von Stationskoordinaten.</small>
</div>
@once<script src="{{ asset('js/nova-postal-lookup.js') }}" defer></script>@endonce

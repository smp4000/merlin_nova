/* Bewusste Einzelabfrage statt Nominatim-Autocomplete. Bestehende/manuell geänderte
   Werte und zwischenzeitlich geänderte PLZ bleiben auch bei verspäteter Antwort geschützt. */
document.querySelectorAll('[data-postal-lookup]').forEach(root => {
    const postal = document.getElementById(root.dataset.postalInput);
    const city = document.getElementById(root.dataset.cityInput);
    const country = document.getElementById(root.dataset.countryInput);
    const button = root.querySelector('[data-lookup-place]');
    const status = root.querySelector('[data-lookup-status]');
    const label = root.querySelector('[data-place-choice-label]');
    const choice = root.querySelector('[data-place-choice]');
    if (!postal || !city) return;
    let lookedUp = '';
    const apply = value => {
        if (postal.value.trim() !== lookedUp || (country && country.value !== 'DE') || city.value.trim()) {
            status.textContent = 'Eingaben wurden geändert. Vorhandener Ort bleibt erhalten; bitte prüfen.';
            label.hidden = true;
            return;
        }
        city.value = value;
        city.dispatchEvent(new Event('change', {bubbles: true}));
        status.textContent = 'Ort aus OpenStreetMap übernommen. Bitte prüfen.';
        label.hidden = true;
    };
    choice.addEventListener('change', () => { if (choice.value) apply(choice.value); });
    postal.addEventListener('input', () => { label.hidden = true; if (lookedUp) status.textContent = 'PLZ geändert. Bitte den Ort prüfen.'; });
    button.addEventListener('click', async () => {
        label.hidden = true;
        if (city.value.trim()) { status.textContent = 'Ein Ort ist bereits eingetragen und bleibt erhalten. Für eine neue Ermittlung das Ortsfeld zuerst leeren.'; return; }
        if (!/^\d{5}$/.test(postal.value.trim()) || (country && country.value !== 'DE')) { status.textContent = 'Bitte eine vollständige deutsche PLZ eingeben und Deutschland wählen.'; return; }
        lookedUp = postal.value.trim();
        const requested = lookedUp;
        button.disabled = true;
        status.textContent = 'Ort wird ermittelt …';
        try {
            const response = await fetch(root.dataset.endpoint, {method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':root.closest('form')?.querySelector('[name="_token"]')?.value || ''}, body:JSON.stringify({postal_code:requested,country_code:'DE'})});
            if (!response.ok) throw new Error('lookup');
            const result = await response.json();
            if (postal.value.trim() !== requested || city.value.trim() || (country && country.value !== 'DE')) { status.textContent = 'Eingaben wurden geändert. Bitte Ort prüfen.'; return; }
            if (result.status !== 'ok' || !Array.isArray(result.cities) || !result.cities.length) { status.textContent = result.status === 'rate_limited' ? 'Abruflimit erreicht. Bitte später erneut versuchen oder Ort manuell eingeben.' : 'Kein eindeutiger Ort verfügbar. Bitte Ort manuell eingeben.'; return; }
            if (result.cities.length === 1) { apply(result.cities[0]); return; }
            choice.replaceChildren(new Option('Bitte Ort auswählen',''), ...result.cities.map(value => new Option(value,value)));
            label.hidden = false;
            status.textContent = 'Mehrere Orte gefunden. Bitte auswählen.';
        } catch { status.textContent = 'Ortsermittlung derzeit nicht verfügbar. Bitte Ort manuell eingeben.'; }
        finally { button.disabled = false; }
    });
});

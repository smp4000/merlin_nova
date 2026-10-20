/**
 * Sammellauf ohne Seitennavigation: ein autorisierter POST je Schritt, danach
 * ausschließlich Preis-/Fortschrittsanzeige ersetzen. Karte und Formular bleiben
 * im DOM. Fehler stoppen ohne Retry; ohne JavaScript gelten normale POST-Forms.
 */
document.addEventListener('DOMContentLoaded', () => {
    let timer, busy = false;
    const widget = () => document.querySelector('#price-refresh');
    function stopWithError(message) {
        window.clearTimeout(timer);
        const root = widget();
        if (!root) return;
        root.querySelector('[data-client-price-error]')?.remove();
        const error = document.createElement('p');
        error.dataset.clientPriceError = 'true';
        error.setAttribute('role', 'alert');
        error.textContent = message + ' Die Aktualisierung wurde angehalten. Bitte bewusst fortsetzen oder die Seite neu öffnen.';
        root.append(error);
    }
    function schedule() {
        window.clearTimeout(timer);
        const form = widget()?.querySelector('[data-price-refresh][data-continue]');
        if (form) timer = window.setTimeout(() => send(form), 4500);
    }
    async function send(form, submitter) {
        window.clearTimeout(timer);
        if (busy) return;
        busy = true;
        const body = new FormData(form);
        if (submitter?.name) body.set(submitter.name, submitter.value);
        const buttons = [...widget().querySelectorAll('button')];
        buttons.forEach(button => button.disabled = true);
        try {
            // Das Eingabefeld name=action verdeckt die native form.action-Eigenschaft.
            const response = await fetch(form.getAttribute('action'), {
                method: 'POST', body, credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (response.redirected || !response.headers.get('content-type')?.includes('application/json')) {
                throw new Error('Die Sitzung ist abgelaufen oder die Antwort konnte nicht verarbeitet werden.');
            }
            const data = await response.json();
            if (!response.ok) {
                const messages = Object.values(data.errors || {}).flat().filter(value => typeof value === 'string');
                throw new Error(messages.join(' ') || (response.status === 429 ? 'Abruflimit erreicht.' : 'Preisaktualisierung fehlgeschlagen.'));
            }
            const parsed = new DOMParser().parseFromString(data.widget, 'text/html');
            const replacement = parsed.querySelector('#price-refresh');
            if (!replacement || !Array.isArray(data.points)) throw new Error('Die Antwort ist unvollständig.');
            widget().replaceWith(replacement);
            document.querySelectorAll('[data-station-map]').forEach(map => {
                map.dispatchEvent(new CustomEvent('nova:prices-updated', { detail: { points: data.points } }));
            });
            const strip = document.querySelector('.source-price-strip');
            if (strip) {
                strip.replaceChildren();
                Object.entries({ e5: 'E5', e10: 'E10', diesel: 'Diesel' }).forEach(([fuel, label]) => {
                    const row = document.createElement('span');
                    row.append(label + ' ');
                    const value = document.createElement('b');
                    value.textContent = data.sourcePrices[fuel] == null ? '–' : String(data.sourcePrices[fuel]).replace('.', ',') + ' €/l';
                    row.append(value);
                    if (data.lastKnown[fuel]) {
                        const note = document.createElement('small'); note.textContent = 'zuletzt bekannt'; row.append(note);
                    }
                    strip.append(row);
                });
            }
            const stamp = document.querySelector('.source-summary-stamp');
            if (stamp && data.sourceStamp) stamp.textContent = data.sourceStamp;
            if (data.sourcePreview) {
                const preview = new DOMParser().parseFromString(data.sourcePreview, 'text/html').querySelector('.provider-preview');
                const current = document.querySelector('.source-disclosure-content > .provider-preview');
                if (preview && current) current.replaceWith(preview);
            }
            schedule();
        } catch (error) {
            // Ein unklarer Netzwerkstatus darf keine automatische Doppelanfrage
            // auslösen. Bereits serverseitig gespeicherte Schritte bleiben bestehen.
            stopWithError(error instanceof TypeError ? 'Verbindung oder Antwortverarbeitung fehlgeschlagen.' : error instanceof Error ? error.message : 'Verbindung fehlgeschlagen.');
        } finally {
            busy = false;
            buttons.forEach(button => button.disabled = false);
        }
    }
    document.addEventListener('submit', event => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.closest('#price-refresh')) return;
        event.preventDefault();
        send(form, event.submitter);
    });
    schedule();
});

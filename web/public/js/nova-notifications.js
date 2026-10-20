/* Meldungen bleiben bis zum bewussten Schließen lesbar, insbesondere Fehler.
 * Keine Übertragung, Speicherung oder automatische Wiederholung von Versandaktionen. */
document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-dismiss-notification]');
    if (button) button.closest('.nova-notification').hidden = true;
});
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!form.matches('[data-invitation-send]')) return;
    if (form.dataset.sending === 'true') { event.preventDefault(); return; }
    form.dataset.sending = 'true';
    const button = form.querySelector('button[type="submit"], button:not([type])');
    if (button) { button.disabled = true; button.textContent = 'Einladung wird versendet …'; }
});
window.addEventListener('pageshow', () => {
    document.querySelectorAll('[data-invitation-send]').forEach((form) => {
        delete form.dataset.sending;
        const button = form.querySelector('button');
        if (button?.disabled) { button.disabled = false; button.textContent = 'Per E-Mail einladen'; }
    });
});

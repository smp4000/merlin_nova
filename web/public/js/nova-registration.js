/* Ausschließlich lokale Eingabehilfe, niemals Ersatz der PHP-Prüfung.
   Keine Kontodatenübertragung, kein Speichern in Browserdatenbanken/Logs. */
const ibanInput = document.querySelector('#iban');
const ibanStatus = document.querySelector('#iban-status');
if (ibanInput && ibanStatus) {
    const countryLengths = JSON.parse(ibanInput.dataset.countryLengths);
    ibanInput.addEventListener('blur', () => {
        const iban = ibanInput.value.replace(/\s+/g, '').toUpperCase();
        if (!iban) { ibanStatus.textContent = ''; return; }
        const validShape = /^[A-Z]{2}[0-9]{2}[A-Z0-9]+$/.test(iban)
            && iban.length === countryLengths[iban.slice(0, 2)]
            && (!iban.startsWith('DE') || /^DE[0-9]+$/.test(iban));
        let remainder = 0;
        for (const character of iban.slice(4) + iban.slice(0, 4)) {
            const digits = /^[A-Z]$/.test(character) ? String(character.charCodeAt(0) - 55) : character;
            for (const digit of digits) { remainder = (remainder * 10 + Number(digit)) % 97; }
        }
        ibanStatus.textContent = validShape && remainder === 1
            ? 'Format und Prüfziffer plausibel – keine Konto- oder Inhaberbestätigung.'
            : 'Bitte Länderformat, Länge und Prüfziffer kontrollieren.';
    });
}

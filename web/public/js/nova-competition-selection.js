/**
 * Nur die bewusst ausgewählte, serverseitig begrenzte Prüffolge fortsetzen.
 * Ein POST prüft genau einen Standort. Abstand schützt den bestehenden Quell-
 * cooldown; Fehler stoppen die Folge ohne automatische Wiederholung. Speichern
 * erfolgt ausschließlich über den gesonderten Bestätigungsbutton des Nutzers.
 */
document.addEventListener('DOMContentLoaded', () => {
    const choices = [...document.querySelectorAll('input[name="source_ids[]"]')];
    const selectionForm = choices[0]?.closest('form');
    if (selectionForm) {
        const button = selectionForm.querySelector('.competition-choice-actions button');
        const counter = document.createElement('span');
        counter.setAttribute('role', 'status');
        selectionForm.querySelector('.competition-choice-actions')?.append(counter);
        const update = () => {
            const count = choices.filter(input => input.checked).length;
            counter.textContent = `${count} ausgewählt`;
            if (button) button.disabled = count === 0 || count > 8;
            choices.forEach(input => {
                if (!input.dataset.unavailable) {
                    input.disabled = !input.checked && count >= 8;
                }
            });
        };
        choices.forEach(input => {
            if (input.disabled) input.dataset.unavailable = 'true';
            input.addEventListener('change', update);
        });
        update();
    }
    const scan = document.querySelector('[data-distance-scan][data-continue]');
    const form = scan || document.querySelector('[data-competition-check][data-continue]');
    if (!form) return;
    // Durch POST/Redirect/GET entstehen zwei Seitenaufrufe je Schritt. 4,5 s
    // Abstand halten auch das bestehende Routenlimit ein; Stundenquote gilt weiter.
    const timer = window.setTimeout(() => form.requestSubmit(), scan ? 4500 : 2500);
    document.addEventListener('submit', () => window.clearTimeout(timer), { once: true });
    form.addEventListener('submit', () => {
        window.clearTimeout(timer);
        const button = form.querySelector('button');
        if (button) {
            button.disabled = true;
            button.textContent = 'Standort wird geprüft …';
        }
    }, { once: true });
});

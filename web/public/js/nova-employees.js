/* Keine Übertragung beim Tippen, kein LocalStorage, keine automatischen Mails.
   Das Fragment-Geheimnis wird vor anderen Aktionen aus der Adresszeile entfernt. */
(() => {
    const accept = document.querySelector('[data-invitation-accept]');
    if (accept) {
        const secret = window.location.hash.slice(1);
        window.history.replaceState(null, '', window.location.pathname);
        if (/^[A-Za-z0-9]{64}$/.test(secret)) accept.querySelector('[name="token"]').value = secret;
    }
    // Der Listenlink öffnet nur die Bestätigung, niemals eine Löschaktion.
    if (window.location.hash === '#delete') {
        const deletion = document.getElementById('delete');
        if (deletion?.tagName === 'DETAILS') deletion.open = true;
    }
    // Dieselben Länderlängen wie auf dem Server werden im HTML bereitgestellt.
    // Modulo 97 stellenweise berechnen, niemals lange Kontonummern als JS-Zahl.
    // Kein externer Prüfservice und keine Übertragung beim Tippen.
    document.querySelectorAll('[data-iban-input]').forEach(input => {
        const hint = document.getElementById(`${input.id}-iban-hint`);
        const lengths = JSON.parse(input.dataset.ibanLengths);
        const check = (normalize = false) => {
            const iban = input.value.replace(/\s+/gu, '').toUpperCase();
            if (normalize) input.value = iban;
            let error = '';
            if (iban) {
                const format = /^[A-Z]{2}[0-9]{2}[A-Z0-9]+$/.test(iban);
                const length = lengths[iban.slice(0, 2)];
                if (!format) error = 'Bitte eine IBAN mit Ländercode, Prüfziffer und Kontonummer eingeben.';
                else if (!length) error = 'Dieser IBAN-Ländercode wird derzeit nicht unterstützt.';
                else if (iban.length !== length) error = `Diese IBAN muss ${length} Zeichen enthalten.`;
                else if (iban.startsWith('DE') && !/^[0-9]+$/.test(iban.slice(2))) error = 'Eine deutsche IBAN darf nach DE nur Ziffern enthalten.';
                else {
                    let remainder = 0;
                    for (const character of iban.slice(4) + iban.slice(0, 4)) {
                        const digits = /[A-Z]/.test(character) ? String(character.charCodeAt(0) - 55) : character;
                        for (const digit of digits) remainder = (remainder * 10 + Number(digit)) % 97;
                    }
                    if (remainder !== 1) error = 'Die IBAN-Prüfziffer ist ungültig. Bitte die Eingabe prüfen.';
                }
            }
            input.setCustomValidity(error);
            input.setAttribute('aria-invalid', error ? 'true' : 'false');
            hint.textContent = error || (iban ? 'IBAN formal gültig. Keine Konto- oder Inhaberbestätigung.' : 'Lokale Format- und Prüfzifferprüfung. Keine Konto- oder Inhaberbestätigung.');
            hint.dataset.state = error ? 'error' : (iban ? 'valid' : 'empty');
        };
        input.addEventListener('input', () => check());
        input.addEventListener('blur', () => check(true));
        check();
    });

    // Progressive Verbesserung: Ohne JavaScript bleiben alle Gruppen sichtbar.
    // Schritte deaktivieren keine Eingaben; jede Speicherung prüft weiterhin den
    // vollständigen erlaubten Datensatz auf dem Server. Kein Browser-Speicher.
    document.querySelectorAll('[data-employee-wizard]').forEach(form => {
        const steps = [...form.querySelectorAll('[data-wizard-step]')];
        const navigation = form.querySelector('[data-wizard-navigation]');
        const progress = form.querySelector('[data-wizard-progress]');
        const back = form.querySelector('[data-wizard-back]');
        const next = form.querySelector('[data-wizard-next]');
        let current = 0;
        const review = () => {
            const target = form.querySelector('[data-wizard-review]');
            target.replaceChildren();
            steps.slice(0, -1).forEach((step, index) => {
                const card = document.createElement('section');
                card.className = 'employee-review-card';
                const title = document.createElement('h3');
                title.textContent = step.dataset.wizardLabel;
                card.append(title);
                const list = document.createElement('dl');
                step.querySelectorAll('.field').forEach(field => {
                    const input = field.querySelector('input,select,textarea');
                    if (!input?.value.trim()) return;
                    const label = document.createElement('dt');
                    label.textContent = field.querySelector('label').textContent.replace('*', '').trim();
                    const value = document.createElement('dd');
                    const sensitive = input.matches('[data-iban-input]') || /\[(tax_id|social_security_number)\]$/.test(input.name);
                    value.textContent = sensitive ? `•••• ${input.value.replace(/\s/g, '').slice(-4)}` : (input.tagName === 'SELECT' ? input.selectedOptions[0].textContent : input.value);
                    list.append(label, value);
                });
                if (!list.children.length) {
                    const empty = document.createElement('p'); empty.textContent = 'Keine Angaben'; card.append(empty);
                } else card.append(list);
                const edit = document.createElement('button');
                edit.type = 'button'; edit.className = 'button secondary'; edit.textContent = `${step.dataset.wizardLabel} prüfen`;
                edit.addEventListener('click', () => show(index)); card.append(edit); target.append(card);
            });
        };
        const show = (index, focus = true) => {
            current = Math.max(0, Math.min(index, steps.length - 1));
            form.querySelector('[data-wizard-position]').value = String(current);
            steps.forEach((step, i) => { step.hidden = i !== current; });
            navigation.querySelectorAll('[data-wizard-go]').forEach((button, i) => {
                if (i === current) button.setAttribute('aria-current', 'step');
                else button.removeAttribute('aria-current');
                button.classList.toggle('has-errors', steps[i].hasAttribute('data-wizard-error'));
            });
            progress.textContent = `Schritt ${current + 1} von ${steps.length} · ${steps[current].dataset.wizardLabel}`;
            back.hidden = current === 0;
            next.hidden = current === steps.length - 1;
            if (current === steps.length - 1) review();
            if (focus) steps[current].querySelector('h2').focus();
        };
        navigation.hidden = false; progress.hidden = false;
        form.classList.add('is-wizard');
        navigation.addEventListener('click', event => {
            const button = event.target.closest('[data-wizard-go]');
            if (button) show(Number(button.dataset.wizardGo));
        });
        back.addEventListener('click', () => show(current - 1));
        next.addEventListener('click', () => {
            const invalid = [...steps[current].querySelectorAll('input,select,textarea')].find(input => !input.checkValidity());
            if (invalid) { invalid.closest('details')?.setAttribute('open', ''); invalid.reportValidity(); return; }
            show(current + 1);
        });
        // Ein ungültiges Feld in einem anderen Schritt muss vor dem Fokus sichtbar
        // werden; native Browservalidierung darf niemals an hidden Feldern scheitern.
        form.addEventListener('invalid', event => {
            const index = steps.indexOf(event.target.closest('[data-wizard-step]'));
            if (index >= 0 && steps[index].hidden) show(index, false);
        }, true);
        // Browser prüfen sonst alle versteckten Schritte und versuchen anschließend
        // ein inzwischen wieder unsichtbares erstes Fehlerfeld zu fokussieren.
        // Wir zeigen genau das erste ungültige Feld; der Server prüft unabhängig.
        form.noValidate = true;
        form.addEventListener('submit', event => {
            const invalid = [...form.querySelectorAll('input,select,textarea')].find(input => !input.checkValidity());
            if (!invalid) return;
            event.preventDefault();
            const index = steps.indexOf(invalid.closest('[data-wizard-step]'));
            if (index >= 0) show(index, false);
            invalid.closest('details')?.setAttribute('open', '');
            invalid.reportValidity();
        });
        form.addEventListener('keydown', event => {
            if (event.key === 'Enter' && event.target.matches('input:not([type="checkbox"])') && current < steps.length - 1) {
                event.preventDefault(); next.click();
            }
        });
        const firstError = steps.findIndex(step => step.hasAttribute('data-wizard-error'));
        show(firstError < 0 ? (Number(form.dataset.wizardStart) || 0) : firstError, false);
    });

    document.querySelectorAll('[data-repeat-group]').forEach(group => {
        const rows = group.querySelector('[data-repeat-rows]');
        group.querySelector('[data-add-row]')?.addEventListener('click', () => {
            const current = rows.querySelectorAll('[data-repeat-row]');
            if (current.length >= 20) return;
            const clone = current[0].cloneNode(true);
            const index = current.length;
            clone.querySelector('legend').textContent = `Eintrag ${index + 1}`;
            clone.querySelectorAll('input,select,textarea').forEach(input => {
                const previous = input.id;
                input.name = input.name.replace(/\[\d+\]/, `[${index}]`);
                input.id = previous.replace(/-\d+-/, `-${index}-`);
                clone.querySelector(`label[for="${previous}"]`)?.setAttribute('for', input.id);
                input.value = '';
            });
            rows.append(clone);
            clone.querySelector('input,select')?.focus();
        });
        group.addEventListener('click', event => {
            if (!event.target.matches('[data-remove-row]')) return;
            event.target.closest('[data-repeat-row]').querySelectorAll('input,select,textarea').forEach(input => { input.value = ''; });
        });
    });
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('invalid', event => {
            let parent = event.target.parentElement;
            while (parent) { if (parent.tagName === 'DETAILS') parent.open = true; parent = parent.parentElement; }
        }, true);
    });
})();

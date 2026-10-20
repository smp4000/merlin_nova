/* Progressive Erweiterung: ohne JavaScript bleiben alle Gruppen sichtbar und
   Formular/Servervalidierung vollständig nutzbar. Tabs vergeben keine Rechte. */
(() => {
    const root = document.querySelector('[data-station-details]');
    if (!root) return;
    const tabs = [...root.querySelectorAll('[data-station-tab]')];
    const panels = [...root.querySelectorAll('[data-station-panel]')];
    root.querySelector('.station-tabs').setAttribute('role', 'tablist');
    const show = (name, focus = false) => {
        if (!panels.some(panel => panel.dataset.stationPanel === name)) name = 'address';
        panels.forEach(panel => {
            panel.hidden = panel.dataset.stationPanel !== name;
            panel.setAttribute('role', 'tabpanel');
        });
        tabs.forEach(tab => {
            const selected = tab.dataset.stationTab === name;
            tab.setAttribute('role', 'tab');
            tab.setAttribute('aria-selected', String(selected));
            tab.setAttribute('aria-controls', 'section-' + tab.dataset.stationTab);
            tab.tabIndex = selected ? 0 : -1;
            if (selected && focus) tab.focus();
        });
    };
    tabs.forEach((tab, index) => {
        tab.addEventListener('click', event => {
            event.preventDefault();
            show(tab.dataset.stationTab);
            history.replaceState(null, '', tab.getAttribute('href'));
        });
        tab.addEventListener('keydown', event => {
            let next;
            if (event.key === 'ArrowRight') next = (index + 1) % tabs.length;
            if (event.key === 'ArrowLeft') next = (index + tabs.length - 1) % tabs.length;
            if (event.key === 'Home') next = 0;
            if (event.key === 'End') next = tabs.length - 1;
            if (next === undefined) return;
            event.preventDefault();
            show(tabs[next].dataset.stationTab, true);
        });
    });
    const firstError = root.querySelector('[data-field-error]')?.closest('[data-station-panel]');
    show(firstError?.dataset.stationPanel || (location.hash === '#price-refresh' ? 'map' : location.hash.replace('#section-', '')));
    root.addEventListener('invalid', event => {
        // Selten benötigte Felder dürfen bei einem Fehler nicht im eingeklappten
        // Bereich verborgen bleiben. Native Validierung öffnet alle Vorfahren.
        let disclosure = event.target.closest('details');
        while (disclosure) {
            disclosure.open = true;
            disclosure = disclosure.parentElement?.closest('details');
        }
        const panel = event.target.closest('[data-station-panel]');
        if (panel) show(panel.dataset.stationPanel);
    }, true);

    const list = root.querySelector('[data-repeat-list]');
    const add = root.querySelector('[data-add-competitor]');
    if (!list || !add) return;
    const prototype = list.querySelector('[data-competitor-row]').cloneNode(true);
    const refresh = () => {
        const rows = [...list.querySelectorAll('[data-competitor-row]')];
        rows.forEach((row, index) => {
            row.querySelector('legend').textContent = 'Wettbewerbsstandort ' + (index + 1);
            row.querySelectorAll('input').forEach(input => {
                input.name = input.name.replace(/\[competitors\]\[\d+\]/, '[competitors][' + index + ']');
                const oldId = input.id;
                input.id = oldId.replace(/competitor-\d+-/, 'competitor-' + index + '-');
                row.querySelector('label[for="' + oldId + '"]')?.setAttribute('for', input.id);
            });
            row.querySelector('[data-move-competitor]').hidden = index === 0;
            row.querySelector('[data-remove-competitor]').hidden = false;
        });
        add.hidden = false;
        add.disabled = rows.length >= Number(list.dataset.repeatLimit);
    };
    add.addEventListener('click', () => {
        if (list.children.length >= Number(list.dataset.repeatLimit)) return;
        const row = prototype.cloneNode(true);
        row.querySelectorAll('input').forEach(input => { input.value = ''; });
        list.append(row);
        refresh();
        row.querySelector('input')?.focus();
    });
    list.addEventListener('click', event => {
        const row = event.target.closest('[data-competitor-row]');
        if (!row) return;
        if (event.target.closest('[data-remove-competitor]')) row.remove();
        if (event.target.closest('[data-move-competitor]') && row.previousElementSibling) list.insertBefore(row, row.previousElementSibling);
        refresh();
    });
    refresh();
})();

/* Bankzeilen bleiben ein eigener Eingabebereich. Weder dieses Script noch der
   Server führt eine Bankabfrage oder IBAN-Berechnung aus. */
(() => {
    const list = document.querySelector('[data-bank-list]');
    const add = document.querySelector('[data-add-bank]');
    if (!list || !add) return;
    const prototype = list.querySelector('[data-bank-row]').cloneNode(true);
    const refresh = () => {
        [...list.children].forEach((row, index) => {
            row.querySelector('legend').textContent = 'Bankkonto ' + (index + 1);
            row.querySelectorAll('input').forEach(input => {
                input.name = input.name.replace(/\[bank_accounts\]\[\d+\]/, '[bank_accounts][' + index + ']');
                const oldId = input.id;
                input.id = oldId.replace(/bank-\d+-/, 'bank-' + index + '-');
                row.querySelector('label[for="' + oldId + '"]')?.setAttribute('for', input.id);
            });
            row.querySelector('[data-remove-bank]').hidden = false;
        });
        add.hidden = false;
        add.disabled = list.children.length >= 10;
    };
    add.addEventListener('click', () => {
        if (list.children.length >= 10) return;
        const row = prototype.cloneNode(true);
        row.querySelectorAll('input').forEach(input => { input.value = ''; });
        list.append(row);
        refresh();
        row.querySelector('input').focus();
    });
    list.addEventListener('click', event => {
        if (event.target.closest('[data-remove-bank]')) event.target.closest('[data-bank-row]').remove();
        refresh();
    });
    refresh();
})();

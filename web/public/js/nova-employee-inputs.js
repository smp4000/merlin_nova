/* Bankhilfe verarbeitet Kontonummer/IBAN ausschließlich lokal. An den eigenen
 * Referenzendpunkt gehen nur Bankname/Ort oder die achtstellige öffentliche BLZ.
 * Ergebnisse nie still in bestehende manuelle Angaben übernehmen. */
(() => {
    const normalize = value => value.replace(/\s+/gu, '').toUpperCase();
    const remainder = digits => [...digits].reduce((rest, digit) => (rest * 10 + Number(digit)) % 97, 0);
    const validGerman = iban => /^DE[0-9]{20}$/.test(iban) && remainder(iban.slice(4) + '1314' + iban.slice(2, 4)) === 1;
    document.querySelectorAll('[data-bank-tools]').forEach(helper => {
        const dialog = helper.querySelector('[data-bank-dialog]');
        const ibanInput = document.getElementById(helper.dataset.ibanId);
        const bicInput = document.getElementById(helper.dataset.bicId);
        const nameInput = document.getElementById(helper.dataset.nameId);
        const status = dialog.querySelector('[data-bank-status]');
        const results = dialog.querySelector('[data-bank-results]');
        const blzInput = dialog.querySelector('[data-bank-blz]');
        const accountInput = dialog.querySelector('[data-bank-account]');
        const confirmation = dialog.querySelector('[data-bank-confirm]');
        const replacement = dialog.querySelector('[data-bank-replace]');
        let selected = null, currentDirectory = false, proposal = '', mode = 'lookup', opener = null;
        const load = async (path) => {
            status.textContent = 'Bankverzeichnis wird geprüft …';
            const response = await fetch(path, {headers:{Accept:'application/json'}, credentials:'same-origin'});
            if (!response.ok) throw new Error('reference');
            const data = await response.json();
            currentDirectory = data.current === true;
            dialog.querySelector('[data-bank-source]').textContent = `Quelle: ${data.source} · gültig ${data.valid_from} bis ${data.valid_until}`;
            if (!currentDirectory) status.textContent = 'Das lokale Bankverzeichnis ist veraltet. Bitte Bank/BIC manuell anhand der Bankunterlagen angeben.';
            return data.banks;
        };
        const choose = bank => {
            selected = bank; blzInput.value = bank.blz; proposal = ''; confirmation.checked = false;
            dialog.querySelector('[data-bank-proposal]').textContent = '';
            status.textContent = currentDirectory ? `${bank.name} · ${bank.city} · BLZ ${bank.blz} · BIC ${bank.bic || 'nicht hinterlegt'}` : 'Verzeichnis veraltet; keine automatische Übernahme.';
        };
        helper.querySelectorAll('[data-bank-open]').forEach(button => button.addEventListener('click', async () => {
            opener = button; mode = button.dataset.bankOpen; selected = null; proposal = ''; currentDirectory = false;
            results.replaceChildren(); replacement.checked = false; confirmation.checked = false;
            dialog.querySelector('[data-bank-calculator]').hidden = mode !== 'calculator';
            dialog.querySelector('[data-bank-proposal]').textContent = ''; status.textContent = '';
            dialog.showModal();
            const iban = normalize(ibanInput.value);
            if (/^DE[0-9]{20}$/.test(iban)) {
                blzInput.value = iban.slice(4,12);
                try { const banks = await load(`/reference/banks/${blzInput.value}`); if(banks.length===1) choose(banks[0]); else status.textContent = 'BLZ nicht eindeutig im aktiven Verzeichnis gefunden.'; }
                catch { status.textContent = 'Bankverzeichnis nicht verfügbar. Manuelle Eingabe bleibt möglich.'; }
            } else dialog.querySelector('[data-bank-search]').focus();
        }));
        dialog.querySelectorAll('[data-bank-close]').forEach(button => button.addEventListener('click', () => dialog.close()));
        dialog.addEventListener('close', () => { accountInput.value = ''; proposal = ''; confirmation.checked = false; opener?.focus(); });
        helper.closest('form')?.addEventListener('submit', event => { if(dialog.open) event.preventDefault(); });
        dialog.addEventListener('keydown', event => {
            if(event.key==='Enter' && event.target.tagName==='INPUT') {
                event.preventDefault(); event.stopPropagation();
                dialog.querySelector(event.target.matches('[data-bank-search]') ? '[data-bank-find]' : '[data-bank-calculate]').click();
            }
        });
        dialog.querySelector('[data-bank-find]').addEventListener('click', async () => {
            const query = dialog.querySelector('[data-bank-search]').value.trim();
            if(query.length<3 || query.length>80 || /[A-Z]{2}\s*\d{2}[\s\d]{10,}/i.test(query)) { status.textContent = 'Bitte mindestens drei Zeichen Bankname/Ort oder eine BLZ eingeben, keine IBAN.'; return; }
            try {
                const banks = await load(`/reference/banks?q=${encodeURIComponent(query)}`); results.replaceChildren();
                banks.forEach(bank => { const button = document.createElement('button'); button.type='button'; button.className='button secondary'; button.textContent=`${bank.name} · ${bank.city} · ${bank.blz}`; button.addEventListener('click',()=>choose(bank)); results.append(button); });
                if(currentDirectory) status.textContent = banks.length ? 'Bitte den passenden Bankeintrag auswählen.' : 'Keine passende Bank gefunden. Manuelle Angaben bleiben möglich.';
            } catch { status.textContent = 'Bankverzeichnis nicht verfügbar. Bitte später erneut prüfen.'; }
        });
        const invalidateProposal = () => { proposal=''; selected=null; confirmation.checked=false; dialog.querySelector('[data-bank-proposal]').textContent=''; };
        blzInput.addEventListener('input', invalidateProposal); accountInput.addEventListener('input', invalidateProposal);
        dialog.querySelector('[data-bank-calculate]').addEventListener('click', async () => {
            const blz = blzInput.value.trim(), account = accountInput.value.trim();
            if(!/^\d{8}$/.test(blz) || !/^\d{1,10}$/.test(account)) { status.textContent='Bitte 8 Ziffern BLZ und 1 bis 10 Ziffern Kontonummer eingeben.'; return; }
            try {
                const banks = await load(`/reference/banks/${blz}`);
                if(!currentDirectory || banks.length!==1) { status.textContent='Kein aktueller eindeutiger Bankeintrag. Bitte die IBAN direkt aus den Bankunterlagen übernehmen.'; return; }
                choose(banks[0]); const bban=blz+account.padStart(10,'0');
                proposal=`DE${String(98-remainder(bban+'131400')).padStart(2,'0')}${bban}`;
                dialog.querySelector('[data-bank-proposal]').textContent=`Formaler Vorschlag: ${proposal}`;
            } catch { status.textContent='Berechnung nicht verfügbar. Bitte die Bankunterlagen verwenden.'; }
        });
        dialog.querySelector('[data-bank-apply]').addEventListener('click', () => {
            const iban = mode==='calculator' ? proposal : normalize(ibanInput.value);
            if(!selected || !currentDirectory || !validGerman(iban) || iban.slice(4,12)!==selected.blz) { status.textContent='Bitte eine formal gültige deutsche IBAN und den dazu passenden Bankeintrag prüfen.'; return; }
            if(mode==='calculator' && !confirmation.checked) { status.textContent='Bitte den Vorschlag erst mit den Bankunterlagen abgleichen und bestätigen.'; return; }
            const next = [[nameInput,selected.name],[bicInput,/^[A-Z]{6}[A-Z0-9]{2}([A-Z0-9]{3})?$/.test(selected.bic) ? selected.bic : '']];
            if(mode==='calculator') next.push([ibanInput,iban]);
            const conflicts=next.some(([input,value])=>value && input.value.trim() && normalize(input.value)!==normalize(value));
            if(conflicts && !replacement.checked) { status.textContent='Vorhandene Angaben weichen ab. Bitte den bewussten Ersatz bestätigen oder abbrechen.'; return; }
            next.forEach(([input,value])=>{ if(value) { input.value=value; input.dispatchEvent(new Event('input',{bubbles:true})); input.dispatchEvent(new Event('change',{bubbles:true})); } });
            dialog.close();
        });
    });
})();

// Kamerafotos bleiben lokal: verkleinern und neu kodieren, bevor die Datei
// ausdrücklich hochgeladen wird. Keine Cloud-Verarbeitung und kein Auto-Upload.
document.querySelectorAll('input[type="file"][name="document"]').forEach(input => {
    const hint=document.createElement('small'); hint.setAttribute('role','status'); input.after(hint);
    let preparing=false;
    input.form.addEventListener('submit',event=>{if(preparing){event.preventDefault();hint.textContent='Das Foto wird noch vorbereitet. Bitte kurz warten.';}});
    input.addEventListener('change',async()=>{
        const file=input.files[0]; if(!file || !['image/jpeg','image/png'].includes(file.type))return;
        preparing=true; hint.textContent='Foto wird auf diesem Gerät vorbereitet …';
        let bitmap;
        try {
            bitmap=await createImageBitmap(file);
            const ratio=Math.min(1,1800/Math.max(bitmap.width,bitmap.height));
            const canvas=document.createElement('canvas'); canvas.width=Math.round(bitmap.width*ratio);canvas.height=Math.round(bitmap.height*ratio);
            canvas.getContext('2d').drawImage(bitmap,0,0,canvas.width,canvas.height);
            const blob=await new Promise(resolve=>canvas.toBlob(resolve,'image/jpeg',0.85));
            if(!blob || blob.size>2*1024*1024)throw new Error('size');
            const transfer=new DataTransfer();transfer.items.add(new File([blob],'Beschaeftigungsnachweis.jpg',{type:'image/jpeg'}));input.files=transfer.files;
            hint.textContent='Foto vorbereitet. Erst „hochladen“ speichert es geschützt.';
        } catch { input.value='';hint.textContent='Bitte ein kleineres Foto oder eine PDF-Datei auswählen.'; }
        finally {bitmap?.close();preparing=false;}
    });
});

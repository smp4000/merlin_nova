/* Leaflet wird lokal ausgeliefert. Kacheln beim Öffnen des Kartenbereichs und
 * ausschließlich für den sichtbaren Ausschnitt; weder Offline- noch Vorababruf.
 * Fremde Namen landen nur in Textknoten, niemals in ausführbarem Popup-HTML. */
document.querySelectorAll('[data-station-map]').forEach(root=>{
    let points=JSON.parse(root.dataset.points);
    const canvas=root.querySelector('[data-map-canvas]');
    const fuel=root.querySelector('[data-map-fuel]');
    const list=root.querySelector('[data-map-list]');
    const status=root.querySelector('[data-map-status]');
    let map,markers=[];
    const ranks={blue:'eigene Station',green:'günstigster Wettbewerbspreis',red:'teuerster Wettbewerbspreis',black:'neutraler Wettbewerbsstandort'};
    const price=(p)=>p.prices[fuel.value]===null?'Preis unbekannt':p.prices[fuel.value].toFixed(3).replace('.',',')+' €/l';
    function text(p){
        const stamp=p.stamps[fuel.value];
        const old=stamp && !p.eligible[fuel.value] && p.prices[fuel.value]!==null;
        return `${p.name}${p.address?' · '+p.address:''} · ${ranks[p.colors[fuel.value]]} · ${price(p)} · ${p.last_known[fuel.value]?'zuletzt bekannt · ':old?'veralteter Abruf · ':''}${stamp?new Date(stamp).toLocaleString('de-DE'):p.last_known[fuel.value]?'Preiszeitpunkt unbekannt':p.prices[fuel.value]===null?'kein Preisstand verfügbar':'manuelle Angabe ohne Abrufzeit'}${p.latitude===null?' · Koordinaten fehlen':''}`;
    }
    function render(){
        list.replaceChildren();markers.forEach(marker=>marker.remove());markers=[];
        points.forEach(p=>{
            const row=document.createElement('p');row.textContent=text(p);row.className='map-point-row '+p.colors[fuel.value];list.append(row);
            if(!map || p.latitude===null || p.longitude===null)return;
            const icon=document.createElement('span');icon.className='station-price-pin '+p.colors[fuel.value];icon.textContent=p.prices[fuel.value]===null?'–':price(p);icon.title=p.name;
            const popup=document.createElement('p');popup.textContent=text(p);
            // Innenabstand und Schließen-Button benötigen zusätzlich Platz. Die
            // Standardbreite von Leaflet würde schmale mobile Karten überragen.
            const popupWidth=Math.max(100,Math.min(300,canvas.clientWidth-72));
            const marker=L.marker([p.latitude,p.longitude],{icon:L.divIcon({className:'station-pin-wrap',html:icon.outerHTML,iconSize:[100,38],iconAnchor:[50,38]}),title:p.name+' · '+ranks[p.colors[fuel.value]],alt:p.name+' · '+ranks[p.colors[fuel.value]]}).addTo(map).bindPopup(popup,{minWidth:100,maxWidth:popupWidth});
            markers.push(marker);
            // Nahe Standorte können sich insbesondere mobil überdecken. Die
            // Standortliste bietet deshalb eine eindeutige, tastaturbedienbare
            // Auswahl, ohne die tatsächlichen Koordinaten der Pins zu verschieben.
            const locate=document.createElement('button');
            locate.type='button';locate.className='button secondary';
            locate.textContent='Auf Karte zeigen';
            locate.setAttribute('aria-label',p.name+(p.address?' · '+p.address:'')+' auf Karte zeigen');
            locate.addEventListener('click',()=>{
                marker.openPopup();
                canvas.scrollIntoView({block:'center',behavior:'smooth'});
            });
            row.append(document.createElement('br'),locate);
        });
    }
    fuel.addEventListener('change',render);
    root.addEventListener('nova:prices-updated',event=>{
        if(!Array.isArray(event.detail?.points))return;
        points=event.detail.points;
        root.dataset.points=JSON.stringify(points);
        // Nur Marker/Preistext erneuern. Karteninstanz, Kacheln und Ausschnitt
        // bleiben erhalten; insbesondere kein fitBounds bei einem Preisschritt.
        render();
    });
    render();
    const loadMap=()=>{
        if(map)return;
        const positions=points.filter(p=>p.latitude!==null && p.longitude!==null).map(p=>[p.latitude,p.longitude]);
        if(!positions.length){status.textContent='Noch keine bestätigten Koordinaten vorhanden. Bitte Standorte prüfen.';return;}
        if(typeof L==='undefined'){status.textContent='Die Kartenbibliothek ist nicht verfügbar. Die Standortliste bleibt nutzbar.';return;}
        canvas.hidden=false;
        map=L.map(canvas,{scrollWheelZoom:false,zoomControl:false}).setView(positions[0],13);
        map.on('popupopen',event=>{
            const close=event.popup.getElement()?.querySelector('.leaflet-popup-close-button');
            if(close){close.setAttribute('aria-label','Kartenhinweis schließen');close.title='Kartenhinweis schließen';}
        });
        L.control.zoom({zoomInTitle:'Vergrößern',zoomOutTitle:'Verkleinern'}).addTo(map);
        const tiles=L.tileLayer(root.dataset.tiles,{maxZoom:19,referrerPolicy:'origin',attribution:'© <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap-Mitwirkende</a>'}).addTo(map);
        tiles.on('tileerror',()=>{status.textContent='Kartenkacheln derzeit nicht verfügbar. Standortliste und Preise bleiben nutzbar.';});
        if(positions.length>1)map.fitBounds(positions,{padding:[45,45],maxZoom:15});
        render();
        status.textContent='Karte geladen. Preise beruhen auf dem gespeicherten Abrufstand.';
        new ResizeObserver(()=>map.invalidateSize()).observe(canvas);
    };
    // Ausgeblendete Tabs laden keine Kacheln. Beim Öffnen wird die Karte direkt
    // sichtbar; Observer trennt sich nach dem einmaligen Aufbau wieder ab.
    const observer=new IntersectionObserver(entries=>{
        if(entries.some(entry=>entry.isIntersecting)){loadMap();if(map)observer.disconnect();}
    });
    observer.observe(root);
});

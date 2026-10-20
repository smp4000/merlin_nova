<div class="nova-station-map" data-station-map data-points='@json($mapPoints)' data-tiles="{{ config('station_map.tiles') }}">
<div class="station-map-toolbar"><div class="field"><label for="map-fuel">Preisvergleich</label><select id="map-fuel" data-map-fuel><option value="e5">Super E5</option><option value="e10">Super E10</option><option value="diesel">Diesel</option></select></div></div>
<p class="field-help">Beim Laden werden IP-Adresse und Kartenausschnitt an OpenStreetMap übertragen. Automatisches Laden beim Öffnen des Kartenbereichs; kein Offline-Download.</p>
<div data-map-canvas class="station-map-canvas" role="region" aria-label="Karte der eigenen Station und Wettbewerber" hidden></div>
<p data-map-status role="status"></p><p class="map-legend"><span class="map-key blue">Blau: eigene Station</span><span class="map-key green">Grün: günstigste</span><span class="map-key red">Rot: teuerste</span><span class="map-key black">Schwarz: übrige / unbekannt</span></p>

<details class="map-list-disclosure"><summary>Standortdetails und Kartenauswahl</summary><p class="field-help">Vergleich je Sorte zwischen gespeicherten Wettbewerbern mit Koordinaten und höchstens fünf Minuten alten Quellpreisen. Bei gleichen Preisen neutrale Pins. Historische und manuelle Preise ohne verifizierten Abrufzeitpunkt bleiben neutral. Kartenstand beruht auf gespeicherten Angaben.</p><div data-map-list class="station-map-list"></div></details>
</div>
@once<link rel="stylesheet" href="{{ asset('lib/leaflet-1.9.4/leaflet.css') }}"><link rel="stylesheet" href="{{ asset('css/nova-station-map.css') }}"><script src="{{ asset('lib/leaflet-1.9.4/leaflet.js') }}" defer></script><script src="{{ asset('js/nova-station-map.js') }}" defer></script>@endonce

<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Merlin Nova für Tankstellenpartner: Ihre Stationen. Ihr Team. Alles im Blick. Kostenloser Test ohne automatische Kostenpflicht geplant.">
    <meta name="robots" content="noindex, nofollow">
    <title>Merlin Nova · Ihre Stationen. Ihr Team. Alles im Blick.</title>
    <link rel="stylesheet" href="{{ asset('css/nova-landing.css') }}">
</head>
<body>
<a class="skip" href="#main">Zum Inhalt springen</a>
{{-- Eigene SVG-Symbole halten Icons lokal und skalierbar; keine Icon-Fonts oder Drittanbieterabrufe. --}}
<svg class="icon-library" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
    <symbol id="icon-gift" viewBox="0 0 24 24"><path d="M3 8h18v4H3zM5 12v9h14v-9M12 8v13M12 8H8a3 3 0 1 1 3-3l1 3Zm0 0h4a3 3 0 1 0-3-3l-1 3Z"/></symbol>
    <symbol id="icon-team" viewBox="0 0 32 32"><circle cx="16" cy="10" r="5"/><path d="M7 28v-3a9 9 0 0 1 18 0v3M6 6a5 5 0 0 0 0 10M26 6a5 5 0 0 1 0 10M2 27v-3a7 7 0 0 1 5-7M30 27v-3a7 7 0 0 0-5-7"/></symbol>
    <symbol id="icon-tag" viewBox="0 0 32 32"><path d="m3 17 14-14h12v12L15 29a3 3 0 0 1-4 0l-8-8a3 3 0 0 1 0-4Z"/><circle cx="23" cy="9" r="2"/></symbol>
    <symbol id="icon-cash" viewBox="0 0 32 32"><path d="M9 11V3h16v8M6 13h20l4 11v5H2v-5l4-11ZM2 24h28M11 7h10M8 18h2m4 0h2m4 0h2M8 21h2m4 0h2m4 0h2"/></symbol>
    <symbol id="icon-info" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 11v6m0-10v1"/></symbol>
    <symbol id="icon-station" viewBox="0 0 24 24"><path d="M4 21V4h10v17M2 21h14M6 7h6v5H6zM14 10h3v7a2 2 0 0 0 4 0V7l-3-3M18 5l3 3"/></symbol>
    <symbol id="icon-arrow" viewBox="0 0 24 24"><path d="M3 12h18m-6-6 6 6-6 6"/></symbol>
</svg>
<header class="site-header">
    <div class="wrap navigation">
        <a class="brand" href="{{ route('home') }}" aria-label="Merlin Nova Startseite"><span class="brand-mark">M</span><strong>Merlin Nova</strong></a>
        <nav class="nav-links" aria-label="Hauptnavigation"><a href="#ueberblick">Funktionen</a><a href="#ablauf">So funktioniert’s</a><a href="#tarife">Tarife</a></nav>
        <div class="nav-actions"><a class="login-link" href="{{ route('login') }}">Anmelden</a><a class="button primary" href="#kostenlos-testen">Kostenlos testen</a></div>
    </div>
</header>
<main id="main">
    <section class="hero" aria-labelledby="hero-title">
        <div class="wrap hero-grid">
            <div class="hero-copy">
                <div class="hero-eyebrow"><p class="eyebrow">Tankstellen besser organisieren</p><span class="trial-pill"><svg class="icon" aria-hidden="true"><use href="#icon-gift"/></svg>Kostenloser Test</span></div>
                <h1 id="hero-title">Ihre Stationen. Ihr Team.<br>Alles im Blick.</h1>
                <p class="lead">Die zentrale Plattform für Ihren Tankstellenalltag.</p>
                <a class="button primary hero-button" href="#kostenlos-testen">Kostenlos testen <svg class="icon" aria-hidden="true"><use href="#icon-arrow"/></svg></a>
                <p class="hero-promise"><strong>Keine automatische Verlängerung.</strong><br>Kostenpflichtig nur nach Ihrem ausdrücklichen Abschluss.</p>
                <div class="sepa-note"><svg class="icon" aria-hidden="true"><use href="#icon-info"/></svg><div><strong>SEPA-Mandat bei der Registrierung. Keine Abbuchung im kostenlosen Test.</strong><span>Teststart nach E-Mail-Bestätigung. Danach ohne Abo nur lesender Zugriff.</span></div></div>
            </div>
            {{-- Ausschließlich fiktive Beispieldaten. Die Vorschau darf nie echte Partnerdaten abfragen. --}}
            <aside class="app-preview" aria-label="App-Vorschau mit fiktiven Beispieldaten, kein produktiver Arbeitsbereich">
                <div class="preview-label">App-Vorschau · Beispieldaten</div>
                <div class="preview-shell" aria-hidden="true">
                    <div class="app-sidebar"><div class="app-brand"><span>M</span>Merlin Nova</div><div class="app-menu"><span>⌂ &nbsp; Übersicht</span><span class="selected">▣ &nbsp; Stationen</span><span>♧ &nbsp; Team</span><span>▦ &nbsp; Einsätze</span><span>◇ &nbsp; MHD &amp; Etiketten</span><span>▤ &nbsp; Kasse &amp; Übergaben</span><span>▥ &nbsp; Berichte</span></div><div class="app-sidebar-bottom"><span>⚙ &nbsp; Einstellungen</span><span>ⓘ &nbsp; Hilfe</span></div></div>
                    <div class="app-content">
                        <div class="app-heading"><div><h2>Stationen</h2><p>Ihre Tankstellenstandorte im Überblick.</p></div><span class="app-add">＋ Station anlegen</span></div>
                        <div class="app-columns"><div class="station-list"><div class="app-filters"><span>⌕ &nbsp; Station suchen …</span><span>Alle Standorte &nbsp;⌄</span></div>
                            @foreach ([
                                ['Station Musterstadt', 'Musterstraße 12, 12345 Musterstadt', 'Aktiv', '8 Mitarbeiter'],
                                ['Station Nord', 'Industriestraße 8, 54321 Nordstadt', 'Aktiv', '5 Mitarbeiter'],
                                ['Station West', 'Berliner Allee 18, 10115 Weststadt', 'Eingeschränkt', '4 Mitarbeiter'],
                                ['Station Süd', 'Rosenweg 3, 70173 Südstadt', 'Aktiv', '7 Mitarbeiter'],
                            ] as $example)
                            <div class="station-row"><img src="{{ asset('images/station-background-v1.png') }}" alt="" width="55" height="40"><div class="station-name"><strong>{{ $example[0] }}</strong><small>{{ $example[1] }}</small></div><span class="station-status {{ $example[2] === 'Aktiv' ? '' : 'limited' }}">● {{ $example[2] }}</span><span class="station-team">{{ $example[3] }} &nbsp; ›</span></div>
                            @endforeach
                        </div><div class="quick-start"><h3>Schnell starten</h3><div><b>1</b><p><strong>Station anlegen</strong><small>Neuen Standort erfassen und Stammdaten hinterlegen.</small></p></div><div><b>2</b><p><strong>Mitarbeiter zuordnen</strong><small>Team Ihrem Standort zuweisen.</small></p></div><div><b>3</b><p><strong>Einsatz planen</strong><small>Schichtpläne erstellen und Besetzung sichern.</small></p></div></div></div>
                    </div>
                </div>
            </aside>
        </div>
    </section>
    <section class="onboarding" id="ablauf" aria-label="Vom Partnerkonto zur Station und zum Team"><div class="wrap steps">
        <div class="step"><span class="step-number">1</span><div><h2>Kostenlos registrieren</h2><p>Zugang anlegen und Betrieb einrichten.</p></div></div><span class="step-arrow" aria-hidden="true">⟶</span>
        <div class="step"><span class="step-number">2</span><div><h2>Stationen anlegen</h2><p>Ihre Standorte erfassen und verwalten.</p></div></div><span class="step-arrow" aria-hidden="true">⟶</span>
        <div class="step"><span class="step-number">3</span><div><h2>Mitarbeiter zuordnen</h2><p>Team hinzufügen und Einsätze planen.</p></div></div>
    </div></section>
    <section class="wrap features" id="ueberblick">
        <p class="eyebrow">Funktionen</p><h2>Alles, was Sie für einen reibungslosen Betrieb brauchen.</h2>
        <div class="feature-grid">
            <article class="feature-card"><svg class="feature-icon" aria-hidden="true"><use href="#icon-team"/></svg><div><h3>Team &amp; Verträge</h3><p>Mitarbeiter verwalten, Verträge hinterlegen und Einsätze an Ihren Stationen koordinieren.</p><a href="#fragen">Mehr erfahren <span aria-hidden="true">→</span></a><small>In Vorbereitung</small></div></article>
            <article class="feature-card"><svg class="feature-icon" aria-hidden="true"><use href="#icon-tag"/></svg><div><h3>MHD &amp; Etiketten</h3><p>Mindesthaltbarkeiten im Blick behalten und Etiketten einfach erstellen.</p><a href="#fragen">Mehr erfahren <span aria-hidden="true">→</span></a><small>In Vorbereitung</small></div></article>
            <article class="feature-card"><svg class="feature-icon" aria-hidden="true"><use href="#icon-cash"/></svg><div><h3>Kasse &amp; Übergaben</h3><p>Kassenstände dokumentieren und Übergaben sicher und nachvollziehbar gestalten.</p><a href="#fragen">Mehr erfahren <span aria-hidden="true">→</span></a><small>In Vorbereitung</small></div></article>
        </div>
    </section>
    <section class="tariff-banner" id="tarife"><div class="wrap"><p class="eyebrow">Tarife</p><h2>Erst testen. Dann bewusst entscheiden.</h2><p>Tarife nach Stationen und Funktionsumfang.</p><a class="button primary" href="#angebote">Tarife ansehen <span aria-hidden="true">→</span></a></div></section>
    <section class="wrap offers" id="angebote" aria-label="Veröffentlichte Tarifangebote">
        @if ($offers->isEmpty())<p class="empty">Die Tarife werden gerade vorbereitet. Noch keine veröffentlichten Angebote vorhanden – keine Beispielpreise.</p>
        @else<div class="offer-grid">@foreach ($offers as $offer)<article class="offer-card"><h3>{{ $offer->name }}</h3><p class="description">{{ $offer->description }}</p><div class="price">{{ intdiv($offer->price_cents, 100) }},{{ str_pad((string) ($offer->price_cents % 100), 2, '0', STR_PAD_LEFT) }} <small>EUR</small></div><p>pro {{ $offer->billing_interval === 'monthly' ? 'Monat' : 'Jahr' }} · pro {{ $offer->billing_basis === 'partner' ? 'Partnerfirma' : 'Station' }}</p><p>{{ $offer->station_limit ? 'Bis zu '.$offer->station_limit.' Station(en)' : 'Ohne tarifliches Stationslimit' }}</p><p class="quiet">Nettopreis · Fassung {{ $offer->number }} · noch kein Vertragsabschluss möglich.</p><a class="button primary" href="#kostenlos-testen">Zuerst kostenlos testen</a></article>@endforeach</div>@endif
    </section>
    <section class="wrap trial-details" id="kostenlos-testen"><div><span class="trial-pill"><svg class="icon" aria-hidden="true"><use href="#icon-gift"/></svg>Kostenloser Test</span><h2>{{ $trialDays ? $trialDays.' Tage kostenlos testen' : 'Kostenlos kennenlernen' }}</h2>
        <p>{{ $trialDays ? 'Aktuell konfigurierte Testbedingungen: '.$trialDays.' Tage für bis zu '.$trialStationLimit.' Station(en).' : 'Die Testbedingungen werden gerade vorbereitet. Es wird keine Testdauer aus einem Beispiel automatisch übernommen.' }}</p>
        <p><strong>Die Partnerregistrierung folgt als Nächstes.</strong><br>In dieser lokalen Vorschau können Sie noch keinen Test starten und kein Abo abschließen.</p>
    </div><div class="trial-rules"><p><strong>Keine automatische Kostenpflicht.</strong> Der Test beginnt erst nach Bestätigung Ihrer E-Mail-Adresse.</p><p>Ein kostenpflichtiges Abo braucht Ihren ausdrücklichen Abschluss und ein gültiges Mandat. Nach Testende ohne Abschluss bleibt der Zugang nur lesend.</p><p class="quiet">Kündigung zum Ende des bezahlten Zeitraums. Noch keine Erfassung von Registrierungs- oder Bankdaten auf dieser Seite.</p></div></section>
    <section class="wrap questions" id="fragen"><h2>Kurz und klar.</h2>
        <details><summary>Welche Funktionen stehen schon zur Verfügung?</summary><p>Die lokale Mandanten-/Berechtigungsgrundlage und die Tarif-/Testverwaltung sind umgesetzt. Mitarbeiter und Verträge, Personaleinsatzplanung, MHD-Rundgänge mit Abschriften und Druck, Kassenschichten, Gratisabgaben und Störmeldungen folgen als Fachmodule. Die App-Vorschau zeigt ausschließlich fiktive Beispieldaten.</p></details>
        <details><summary>Was passiert nach dem kostenlosen Test?</summary><p>Keine automatische Verlängerung in ein kostenpflichtiges Abo. Vorgesehen ist ein ausdrücklicher Abschluss mit gültigem SEPA-Mandat. Ohne Abschluss bleibt der Zugang nur lesend; keine sofortige Datenlöschung.</p></details>
        <details><summary>Kann ich meine Partnerfarben verwenden?</summary><p>Frei wählbare Partnerfarben sind für den Partnerbereich vorgesehen. Die öffentliche Landingpage verwendet das vereinbarte Nova-Design in Türkis und Petrol.</p></details>
    </section>
</main>
<footer><div class="wrap footer-row"><div><strong>Merlin Nova</strong><a href="#rechtliches">Impressum</a><a href="#rechtliches">Datenschutz</a><a href="#rechtliches">Kontakt</a></div><span>Lokale Entwicklungsvorschau · Testdauer und Tarife administrativ einstellbar</span></div><details class="wrap legal" id="rechtliches"><summary>Hinweise zur lokalen Vorschau</summary><p>Registrierung und Fachmodule sind noch im Aufbau. Impressum, Datenschutz, Kontaktdaten und Vertragstexte müssen vor Veröffentlichung ergänzt und geprüft werden. Das Tankstellenmotiv ist generiert; keine Darstellung eines Kundenstandorts.</p></details></footer>
</body>
</html>

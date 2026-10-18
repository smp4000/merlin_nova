# Merlin Nova Bauplan MDE

Stand: 17.10.2026. Geplante native Android-Anwendung, keine bereits neu implementierte App. Status im [Leitfaden](../MERLIN-NOVA-LEITFADEN.md).

## Ziel und Umfang

Das MDE ist das Arbeitsgerät in der Station, kein verkleinertes Verwaltungsprogramm. Häufige Abläufe sind schnell erreichbar: MHD-Rundgang, ablaufende Ware, Abschrift, Etikettendruck, Kassenschicht/Übergabe, Gratisabgabe und Störung. Eine sichtbare Warteschlange erklärt, was lokal gesichert, übertragen, bestätigt oder fehlgeschlagen ist.

Personaleinsatzplanung wird in der Web-App erstellt. Spätere Plananzeige oder Verfügbarkeitsmeldung am Gerät ist ein gezielt freizugebender Zusatz, nicht ein zweiter vollständiger Planungseditor. Personal-/Bankakten werden nicht auf alle MDEs synchronisiert.

## M01 Geräteaufnahme und Anmeldung

- Separate Nova-Test-App und eigene Gerätekennung; vorhandene produktive App unangetastet lassen.
- In der Web-App vom Berechtigten erstellte, kurz gültige Einmalregistrierung per Code/QR.
- Gerät einem Mandanten und zulässigen Stationen zuordnen; Gerätezugang und persönlicher Benutzer unterscheiden.
- Benutzeranmeldung/PIN mit serverseitigem Rate-Limit, Sperren und nachvollziehbarem Wechsel.
- Individuell widerrufbare Geräteberechtigung; keine gemeinsam auf allen Geräten gespeicherten Inhaberpasswörter.
- Token und lokale Schlüssel im geeigneten Android-Schlüsselspeicher absichern; keine Geheimnisse im APK oder Git.

Offline-Anmeldung, zulässige Offline-Dauer und Verhalten nach serverseitigem Widerruf müssen als bewusste Sicherheitsgrenze festgelegt werden: Ein offline getrenntes Gerät kann einen neuen Widerruf nicht sofort erfahren. Keine falsche Zusage sofortiger Fernlöschung. Beim Benutzer-/Stationswechsel offene Vorgänge behalten, aber niemals dem neuen Benutzer oder einer anderen Station umetikettieren.

## M02 Bedienung

Große Scan-/Aktionsflächen, klare Statuswörter und verständliche Fehlermeldungen. Sichtbare aktuelle Station und angemeldete Person. Scannerfeedback zusätzlich zu Farbe, Mengenänderungen eindeutig und riskante Abschlüsse mit gezielter Bestätigung. Keine Pop-up-Kette für jeden Scan und keine tiefen Untermenüs.

Scanner über getrennten Adapter anbinden, beispielsweise herstellerspezifisches Intent-/Scannerprofil plus kontrollierte alternative Eingabe. Ein Scanereignis darf nur einmal in den aktiven Vorgang eingehen. Kamera- oder Tastatureingabe nur dort, wo der Arbeitsablauf sie sinnvoll erlaubt. Konkretes Gerät, Android-Version, Hardwaretasten und Scannerprofil gehören zur Abnahme.

## M03 Lokale Daten und Synchronisierung

Vorgeschlagen: lokale persistente Datenbank für relevante Artikel-/Konfigurationsstände, laufende Vorgänge und eine transaktionale Outbox. UI zeigt einen lokalen Zustand erst als gesichert, wenn er tatsächlich dauerhaft gespeichert ist. App-Neustart oder Geräte-Reboot darf den Rundgang nicht löschen.

Jeder Vorgang hat eine unveränderliche ID sowie Mandanten-, Stations-, Geräte- und Benutzerbezug. Schreiboperationen werden mit Idempotenzschlüssel und Inhaltsprüfsumme übertragen. Foto-/Anhangreferenzen, Reihenfolgen und relevante Feldwerte gehören in die Inhaltsprüfung, nicht nur die oberste JSON-Ebene.

| Situation | Vorgesehenes Verhalten |
| --- | --- |
| Gleicher Vorgang nochmals unverändert gesendet | Ursprüngliches Ergebnis zurückliefern, keine zweite Buchung |
| Gleicher Schlüssel mit verändertem Inhalt | Expliziter Konflikt, keine stille Ersetzung |
| Verbindung nach Serverannahme verloren | Mit demselben Schlüssel Ergebnis abholen, nicht neue Aktion erfinden |
| Serverstand inzwischen geändert | Version prüfen, Konflikt verständlich anzeigen und gezielt lösen |
| Foto noch nicht übertragen | Als ausstehend anzeigen; Abschlussregel explizit anwenden |
| App beendet oder Gerät neu gestartet | Gespeicherten Vorgang und Outbox fortsetzen |
| Station oder Benutzer gewechselt | Ursprung unverändert erhalten, offene Vorgänge sichtbar und geschützt behandeln |

Wiederholungen mit begrenztem Backoff, kein endloses Hammern des Servers. Dauerhafte Validierungsfehler benötigen Korrektur statt automatischer Schleife. Quittierte Vorgänge erst nach definierter sicherer Aufbewahrung lokal bereinigen. Ein Logout löscht keine unbestätigten Geschäftsvorgänge heimlich.

## M04 MHD Rundgang und Abschrift

1. Station und Rundgang auswählen/starten; benötigte Referenzdaten und Version festhalten.
2. Artikel scannen; Artikel, bestehende MHDs und relevante Mengen anzeigen.
3. Datum/Menge kontrollieren oder ergänzen; Eingaben auf Plausibilität prüfen.
4. Ware behalten, Abschrift erfassen oder freigegebenen Etikettenauftrag erzeugen.
5. Fortschritt und ausstehende Übertragungen sichtbar halten.
6. Abschluss erst gemäß definierter Server-/Offline-Regel als endgültig anzeigen.

Unbekannter Barcode wird als Meldung erfasst. Artikelstammdaten nicht ohne Rechte am MDE global ändern. Datumswerte dürfen nicht aufgrund einer Zeitzonenumrechnung einen anderen MHD-Tag erhalten. Rücknahme einer Abschrift ist ein eigener berechtigter Vorgang, kein heimliches Löschen.

## M05 Druck vom MDE

Das Gerät wählt nur für Station, Vorlage und Etikett passende freigegebene Druckstellen. Es erstellt einen Backend-Druckauftrag, spricht nicht beliebige Windows-Drucker im WLAN direkt an. Der Druckagent holt den Auftrag ab. Auftrag angenommen, an Windows übergeben, Fehler und unklarer Ausgang sind unterscheidbar.

Ist kein Druckagent erreichbar, wird nicht „gedruckt“ gemeldet. Warteschlange und gezielter Abbruch/Nachdruck sind sichtbar. Offline können benötigte Druckabsichten lokal vorgemerkt werden; ein neuer zentraler Auftrag entsteht erst nach Serverprüfung. Geschäftsvorgang und Papierausgabe bleiben getrennte Zustände, damit ein Druckfehler keine Warenbuchung doppelt erzeugt.

## M06 Kasse und Übergabe

Aktuelle Kassenschicht und erlaubte Aktionen vom Server beziehen. Zählungen verständlich erfassen, Summen/Differenzen prüfen, Entwurf lokal absichern. Endgültiger Schichtwechsel zunächst online und mit geprüftem Versionsstand. Nach Verbindungsabbruch Ergebnis des ursprünglichen Wechsels ermitteln, keinen zweiten Wechsel auslösen.

Zweite Person/Bestätigung, zulässige Abweichungen und Vertretungsrechte sind fachliche Entscheidungen. Ein Mitarbeiterbezug darf nicht einfach aus einer frei eingegebenen ID übernommen werden. Während eines Benutzerwechsels keine fremde Schicht übernehmen, ohne den vorgesehenen Übergabeweg zu durchlaufen.

## M07 Gratisabgaben und Störungen

Gratisabgaben mit wenigen Eingaben und klarer Mengen-/Grundauswahl; Limits und Rechte serverseitig. Störungen mit Bereich/Anlage, Beschreibung, Foto und Dringlichkeit; lokal gesicherte Fotos und Uploadstatus. Abschließen/Zurücknehmen nur mit passendem Recht. Keine automatische Übernahme sämtlicher alter Gutschein-/Bonusfunktionen ohne gesonderte fachliche Freigabe.

## M08 Prüfmatrix

Pflichttests: Flugmodus vor und nach dem Speichern; Netzabbruch nach Servercommit; doppelter Scan; mehrfacher Upload; geändertes Foto unter gleichem Schlüssel; Reboot mit Outbox; zwei Geräte bearbeiten dieselbe Ware; fremde Station/Mandant; abgelaufener/widerrufener Zugang; Benutzerwechsel mit offenen Vorgängen; volle/fehlende lokale Speicherkapazität; beschädigter Anhang; Serverfehler; Versionskonflikt.

Kassentests zusätzlich: paralleler Wechsel, fehlende Quell-/Zielberechtigung, abgelehnte Übergabe erhält alte Schicht, wiederholter Abschluss mit gleicher ID liefert dasselbe Ergebnis.

Mindestens ein ausdrücklich ausgewähltes physisches MDE wird für Scanner und Arbeitsablauf getestet; Emulator-/Unit-Tests reichen hierfür nicht. APK-Freigabe, Installationsweg und Rollback werden dokumentiert. Updates dürfen offene Vorgänge und Datenbankstände nicht verlieren.

## M09 Abnahmereihenfolge

1. Geräteaufnahme, Benutzer-/Stationsbezug und sichere Abmeldung.
2. Scan und Artikelsuche mit lokalem Referenzbestand.
3. Ein kompletter MHD-Rundgang mit persistierter Outbox.
4. Abschrift mit serverseitiger Idempotenz.
5. Ein echter Etikettendruck über einen freigegebenen Agenten.
6. Kassenschicht und sichere Übergabe.
7. Gratisabgabe und Störung.
8. Geräte-/Update-/Wiederanlaufprüfung für Pilotfreigabe.

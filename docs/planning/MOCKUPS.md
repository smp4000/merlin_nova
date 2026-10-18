# Merlin Nova Mockups Landingpage und Partnerbereich

Stand: 18.10.2026. Bildentwürfe zur gemeinsamen Designbesprechung, keine implementierten Oberflächen. Erzeugt mit dem integrierten Bildgenerator, nicht mit einer installierten Web-App.

Entscheidung vom 18.10.2026: zuerst kostenlose Testphase, Dauer und Tarife durch Superadmin frei einstellbar. Teststart nach E-Mail-Bestätigung, nach Testende ohne Abschluss nur lesender Zugriff. SEPA-Daten und Mandat bereits bei Registrierung erfassen, jedoch keine Abbuchung im Test und keine automatische Kostenpflicht. Ein kostenpflichtiges Abo wird später ausdrücklich abgeschlossen; dann muss das Mandat weiterhin gültig sein. Kündigung zum Ende des bezahlten Abrechnungszeitraums. Die folgenden v2-Bilder bilden den geänderten Einstieg ab.

## Entwürfe

1. [Landingpage v2](mockups/landingpage-v2.png): kostenloser Test als Hauptaktion, vorbereitendes SEPA transparent angekündigt, keine automatische Kostenpflicht und Hinweis auf lesenden Zugriff nach Testende.
2. [Partnerregistrierung v3](mockups/partner-registration-v3.png): Konto → SEPA-Mandat → E-Mail-Bestätigung, mit „IBAN prüfen“ und Deutschland-Rechner aus BLZ/Kontonummer. Keine Konto-/Inhaberbestätigung und kein kostenpflichtiger Abschluss; Kündigungsregel für das spätere Abo erläutert. [Vorheriger Entwurf v2](mockups/partner-registration-v2.png) bleibt erhalten.
3. Stationsassistent v2 in zwei Boards: [Station finden und Daten prüfen](mockups/station-wizard-search-v2.png) sowie [Partnerfarbe und Zusammenfassung](mockups/station-wizard-finish-v2.png). Alle vier Schritte zeigen den kostenlosen Test ohne automatischen Aboabschluss. Die Grundgestaltung wurde vom Nutzer am 18.10.2026 positiv bestätigt. Der ältere [Partnereinstieg v1](mockups/partner-onboarding-v1.png) bleibt nur als historische Referenz erhalten.
4. [Partnervertrag und Kündigung v1](mockups/partner-contract-v1.png): bewusster kostenpflichtiger Abschluss nach Testende sowie aktive Vertragsübersicht mit Kündigung zum bezahlten Periodenende und Bestätigung. Tarifpreis ist ein Platzhalter, Oktober 2026 ein fiktiver Beispielzeitraum.
5. [Superadmin Tarife und Testphase v1](mockups/superadmin-subscriptions-v1.png): frei einstellbare Testdauer, Tarifentwurf/Veröffentlichung/Fassung und sichtbare feste Vertrags-/Mandatsregeln. Die gezeigten 30 Tage sind ausschließlich ein Beispiel, kein beschlossener Standard.

Historische Entwürfe bleiben erhalten: [Landingpage v1](mockups/landingpage-v1.png) und [Registrierung/Tarifwahl v1](mockups/partner-registration-v1.png). Sie sind hinsichtlich des neuen Registrierungs-/SEPA-Ablaufs überholt. Der Stationsassistent bleibt als Designreferenz bestehen; sein alter Abohinweis ist noch auf „Kostenloser Test bis …“ umzustellen. Erste Bildentwürfe für kostenpflichtigen Abschluss, Vertragsseite/Kündigungsbestätigung und Superadmin-Tarife liegen jetzt vor, sind aber noch nicht fachlich abgenommen. Detailansichten für Tarifbearbeitung, Aboliste, Rechnungen und SEPA-Exporte sind nicht vollständig ausgearbeitet.

Die Navigation im Vertragsboard ist illustrativ und noch an die abgestimmte Partnernavigation anzupassen. Die beiden Vertragshälften zeigen unterschiedliche Beispielzustände, nicht gleichzeitig aktive Zustände eines einzelnen Kontos. Bei der Umsetzung müssen Preis, Steuerdarstellung, Laufzeit, Beginn und Enddatum vollständig aus dem realen Vertragsstand kommen; Platzhalter dürfen keinen echten Abschluss ermöglichen.

Stationsassistent v2: Beispieldaten und Preise sind fiktiv; Suchanbieter und Datenlizenz bleiben offen. Die Farbvorschau zeigt die Anwendungsoberfläche, nicht eine Veränderung der realen Tankstelle. Der Übergang zu Mitarbeitern wird erst nach erfolgreicher Stationsanlage aktiv. Die gestalterische Bestätigung ist keine technische Abnahme und keine Freigabe für produktive Preisabfragen. Noch zu prüfen: Anbietergrenzen, Fehlerzustände, Rechte, Kontrast für Wunschfarben und parallele Stationsanlage an der Tarifgrenze.

Erzeugungsweg Stationsassistent: integrierter Bildgenerator, Bearbeitung des bisherigen Stationsboards. Promptvorgaben: zwei Boards mit je zwei breiten Ansichten, erst Suche mit manuellem Ausweg und fiktiven Kraftstoffpreisen sowie Prüfung der Stationsdaten, dann frei wählbare markennahe Farbe mit UI-Vorschau und Zusammenfassung; Teststatus, keine automatische Kostenpflicht und Übergang zur Mitarbeiterzuordnung sichtbar. Die Farbvorschau wurde gezielt von einer physischen Stationsillustration zu einer reinen Softwarevorschau korrigiert.

Erzeugungsweg für Vertrag und Superadmin: neue Designboards mit integriertem Bildgenerator, keine Anwendung implementiert. [Verwendete Prompts](mockups/PROMPTS-SUBSCRIPTIONS-V1.md).

Die v2-Bilder wurden visuell auf die genannten Kernaussagen geprüft; es sind keine funktionalen Registrierungs-, SEPA- oder Kündigungsprüfungen. Der Mandatstext, weitere Pflichtfelder und Vertragstexte sind noch nicht rechtsverbindlich ausgearbeitet.

Erzeugungsweg v2: integrierter Bildgenerator, Bearbeitung der bestehenden v1-Bilder; keine Programmierung. [Verwendete Prompts](mockups/PROMPTS-V2.md).

Ergänzung v3: integrierter Bildgenerator, Bearbeitung von `partner-registration-v2.png`; visuell geprüft. Prompt: bestehenden Stil und linke Registrierungsansicht erhalten; rechts neben leerem IBAN-Feld „IBAN prüfen“, Hinweis auf Format-/Prüfzifferprüfung ohne Inhaberbestätigung, offenen Deutschland-Rechner mit leeren Feldern Bankleitzahl und Kontonummer sowie „IBAN berechnen“ ergänzen; Mandat, kostenloser Test, ausdrücklicher späterer Abschluss und Kündigung zum bezahlten Periodenende unverändert erhalten. Keine echten Kontodaten und keine Erfolgsanzeige bei leeren Feldern. Die Funktionen sind nur visualisiert, nicht programmiert.

## Was die Entwürfe festlegen sollen

Ruhige moderne Grundgestaltung, verständliche deutsche Begriffe, klare primäre Aktion, wenige visuelle Ebenen und saubere Ausrichtung. Landingpage nutzt neutrales Nova-Branding, Partnerbereich übernimmt ausgewählte Partnerfarben. Für die Umsetzung gelten volle verfügbare Desktopbreite, typische 27-Zoll-Auflösungen, Tastaturbedienbarkeit und responsives Verhalten; eine Bildcollage ist kein Nachweis dieser technischen Eigenschaften.

## Was ausdrücklich noch nicht festgelegt ist

- Tarifnamen „Basis“/„Erweitert“, Preise, Laufzeiten und einzelne Leistungslisten sind Vorschläge beziehungsweise illustrative Platzhalter.
- Etwaige Angaben wie „mindestens 8 Zeichen“ im Bild sind kein beschlossenes Passwortkonzept.
- AGB-/Datenschutz-Checkbox und weitere Rechtstexte sind keine rechtlich freigegebenen Formulierungen.
- Kraftstoffpreise, Stationsnamen, Firmen und Adressen sind Beispiele, kein Liveabruf und kein Betreiber-Nachweis.
- Die Mockups decken weder vollständige Abrechnung/SEPA noch die gesamte spätere Navigation ab.
- Illustrative Marken-/Stationsdarstellungen sind keine zugesicherte Herstellerpartnerschaft; Bild- und Markenverwendung vor öffentlicher Nutzung prüfen.

Die Oberflächen werden nach der gemeinsamen Freigabe eigens gestaltet. Filament ist eine technische Basis, nicht das unveränderte sichtbare Standardprodukt.

## Prompts und Erzeugungsweg

Erzeugungsweg: integrierter Bildgenerator. Drei eigenständige Generierungen im Stil `ui-mockup`, keine bestehenden Bilder editiert.

Promptkern Landingpage: deutsche Merlin-Nova-SaaS-Landingpage, ruhige Weiß/Grau/Navy/Teal-Palette, Überschrift „Ihre Stationen. Ihr Team. Alles im Blick.“, klare Partnerregistrierung, Ablauf Partner → Stationen → Mitarbeiter, Funktionsbereiche Team/Verträge, MHD/Etiketten, Kasse/Übergaben, Tarifhinweis ohne erfundene Preise oder Zertifikate.

Promptkern Registrierung: zwei passende Desktopansichten für Firmen-/Kontaktkonto mit E-Mailbestätigung und für Tarifwahl, Rechnungsdaten, SEPA-Mandat und Prüfung; zentral admingepflegte Tarife, keine tatsächlichen Vertragsangebote oder Echtdaten.

Promptkern Partnereinstieg: zwei Ansichten für verständliche Einrichtungscheckliste und Stationsassistent „Station finden → Daten prüfen → Partnerfarbe → Abschließen“, fiktive Beispielstationen und ausdrücklich nicht-live Kraftstoffpreise, manuelle Alternative, blaue Partnerfarbe und freie Farbauswahl.

Die Bilder bleiben Entwürfe. Offene Punkte und spätere Freigaben werden im [Leitfaden](../MERLIN-NOVA-LEITFADEN.md) geführt.

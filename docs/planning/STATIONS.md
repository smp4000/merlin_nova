# Stationsdaten und externe Stationssuche – Blueprint aus Nutzerreferenzen

Standabgleich: 20.10.2026, P-069. Referenzplan mit historischer Fortschreibung bis P-036; danach zusätzlich Wettbewerberauswahl, Karte, historische Preise und Sammelaktualisierung bis P-067 umgesetzt. Aktueller Gesamtumfang und Einschränkungen im [Status](../planung/STATUS.md), Detailplanung in [Karte und Wettbewerb](STATION-COMPETITION-MAP.md). Frühere Aussagen zu noch deaktivierten externen Abrufen sind durch die später dokumentierte bewusste Aktivierung überholt; Anbieter-Nutzungsrechte bleiben offen.

## Ergänzung P-036: Kompakte Stammdatenoberfläche

Nutzer fordert weniger Unruhe und Scrollen. Stationsmaske mit kompakten Wochenzeilen, einer Kopfzeile, aufklappbaren Zeitzonen-/Eröffnungsangaben und einer schmalen Quellenleiste umgesetzt. Preise/Quelle/Abrufstand sichtbar, ausführliche Daten und Aktionen einklappbar. Weniger Abstände und drei Felder pro Zeile auf großen Bildschirmen, begrenzte Arbeitsbreite. Speichern bleibt am unteren Ansichtsrand erreichbar. Vollständige Labels, Fehleröffnung und mobile Bedienflächen erhalten; Datenregeln und gespeicherte Werte unverändert.

Identischer Desktopprüfstand: Wochenblock 966 → 344 Pixel (rund 64 % weniger), Dokument 2393 → 1000 Pixel. Desktop, Mobil und schmale Desktopbreite geprüft, kein horizontaler Seitenüberlauf. Mobile Speicherung und Fehleröffnung erfolgreich, 41 bestehende Featuretests / 210 Assertions grün. Details und Grenzen im Leitfaden P-036. Keine externen Abrufe, Altprojekt unverändert, kein Commit/Push.

## Auftrag P-035: Geprüfte Quellenübernahme und PLZ-Ortsermittlung

Freigegeben: Öffnungszeiten/Koordinaten aus vorhandenem Benzinpreis-Quellsnapshot nach eigener Vorschau und ausdrücklicher Auswahl übernehmen, bei Neuanlage und Bestandsstation. Vorhandene manuelle Angaben niemals ersetzen, auch nicht bei zwischenzeitlicher Bearbeitung. Import unter Partner-/Stationsschutz, Versions- und Quellenvergleich; Aktualisierung des Quellsnapshots allein ändert keine Stammdaten. Eindeutige 24h-/geschlossen-/Einzelintervalle einschließlich Übernachtöffnung, gültiges Koordinatenpaar. Mehrfachintervalle/mehrdeutige Angaben werden sichtbar als nicht übernehmbar gekennzeichnet, Feiertage nicht auf Wochentage abgebildet. Keine automatische Betreiberberechtigung.

PLZ-Ermittlung: Nutzer meint OpenStreetMap/Nominatim. Öffentliche Instanz verbietet Autocomplete; daher explizite Aktion „Ort ermitteln“ nach vollständiger deutscher PLZ, keine Abfrage beim Tippen. Bestehenden Ort nicht überschreiben, bei mehreren Orten bewusste Auswahl; keine Übernahme von PLZ-Mittelpunkten als Stationskoordinaten. Fester serverseitiger Geocoding-Endpunkt, wechselbar über Betreiberkonfiguration ohne Softwareupdate; maximal ein nicht gecachter Abruf/Sekunde anwendungsweit, Cache, App-Kennung und Attribution/ODbL-Link. Nur PLZ und Land an die Quelle, keine Personen-/Adressdetails. Manueller Weg bleibt ohne Dienst nutzbar.

Prüfziele: Quellparser mit Grenzfällen, Importbestätigung/Version/fremde Partner/Rollen/Testablauf, Erhalt manueller Werte und bereits belegter Tage/Koordinaten, keine Stammdatenänderung durch Preisrefresh; Geocoder-Fehler, mehrere Orte, Land/PLZ/Cache/Throttle und keine Anfrage bei Texteingabe. Desktop/Mobilbrowserprüfung, keine Änderungen im Altprojekt oder lokalen Konfigurationen, kein Commit/Push.

Umgesetzt und geprüft: `StationSourceImport` mit optimistischer Profil-/Quellenprüfung unter Partnersperre, eigene Bestätigung für Bestandsstationen und Auswahl im Neuanlage-Assistenten. Ausschließlich leere Wochentage bzw. vollständig leeres Koordinatenpaar ergänzen, manuelle Werte inklusive Null/Teilangaben schützen. Eindeutige Einzelintervalle und 24h/geschlossen; Tagesende 24:00 korrekt, doppelte/mehrteilige Angaben und Feiertage nur Hinweis. Letzten Importnachweis im verschlüsselten Profil reserviert erhalten, keine vollständige Historie behauptet.

`PostalPlaceProvider` plus begrenzter authentifizierter Partner-Endpunkt und gemeinsames Formularskript: Klick statt Autocomplete, sieben Tage Cache, globale Zwei-Sekunden-Sperre auf einem Apprechner, 30 Abrufe pro Partner/Stundenfenster, App-Kennung/Attribution, HTTPS ohne Redirects. `NOVA_POSTAL_ENDPOINT` als betreiberseitiger Wechsel-/Abschaltpunkt ohne Änderung der bestehenden lokalen Konfiguration. PLZ-Mittelpunkte werden verworfen. Bestehende Eingaben/verspätete Antworten geschützt; Mehrdeutigkeit verlangt Auswahl. [Öffentliche Nominatim-Regeln](https://operations.osmfoundation.org/policies/nominatim/) gelten zusätzlich, kein SLA.

220 Tests / 1027 Assertions grün, Pint/JS-Syntax/Diff-Prüfung sowie begrenzte echte Abrufe und Desktop-/Mobilbrowserprüfung erfolgreich. Details, verbleibende Grenzen und nächster Schritt im Leitfaden P-035. Kein Altprojekt verändert, kein Commit/Push.

## Ergänzung P-034: Nutzerinitiierter echter HTML-Adapter aktiviert

Auf ausdrücklichen Nutzerauftrag externe Abrufe aktiviert, ohne Anbieteranfrage und ohne behauptete Nutzungsfreigabe. P-033 beschreibt nur noch den vorherigen technischen Stand. `BenzinpreisProvider` ruft öffentliche Such-/Detailseiten über feste HTTPS-Pfade ab. Keine dokumentierte Such-API gefunden; daher explizit HTML-Auswertung, keine JSON-API behauptet. PLZ plus Ort, Umkreis fünf Kilometer, kein Geocoder. Echte Seite mit zusätzlichen Navigationslinks berücksichtigt; Pflichtfelder, Größen-/Zeitgrenzen, Quellkennungen und abweichende kanonische ID geprüft. Kein Redirect, keine freien URLs, keine Geheimnisse.

Manuelle/Quellsuche im Assistenten; Treffer erst nach Auswahl und abschließendem Speichern verknüpft. Bestandsstation über „Benzinpreisanbindung einrichten“ mit eigener Vorschau/Bestätigung verknüpfbar, ohne Stammdatenübernahme. Quellenkennung und Snapshot getrennt vom Stationsprofil und Kurzcode/Gesellschaftsnummer. Eindeutigkeit je Partner, Partnersperre und erneute Schreibautorisierung vor Persistenz. Kein pauschaler Betreiberbeweis durch Auswahl. Aktive Rollen-/Testregeln auch für Suche/Aktualisierung, keine zusätzlichen generischen JSON-Felder.

Quelle und Abrufzeit sichtbar; E5/E10/Diesel aus Hauptanzeige, fehlende/Null-/mehrdeutige Preise unbekannt. Alte Preise nach fünf Minuten als veraltet markiert. Aktualisierung nur auf Knopfdruck, eigener Dateicache 15 Minuten Suche / fünf Minuten Details, gemeinsame Abrufsperre plus Zwei-Sekunden-Abstand und 30 Abrufe/Stundenfenster je Partner. Diese Grenzen ersetzen keine Anbietervereinbarung. Keine Hintergrundjobs, keine Öffnungszeiten-/Koordinatenübernahme, kein Wechsel bestehender Quellen oder Wettbewerberautomatismus.

208 Tests / 968 Assertions grün sowie tatsächlicher begrenzter PHP-/Desktop-/Mobilbrowserabruf und gespeicherte Quellenzuordnung auf getrennter Testdatenbank erfolgreich. Einzelheiten/Belege, Nutzungsrechtslücke und nächste Schritte im Leitfaden P-034. Altprojekt unverändert, kein Commit/Push.

## Ergänzung P-033: Lokale Vorbereitung ohne externen Transport

Freigegeben und umgesetzt: `App\Stations\Providers\StationDataProvider`, ausschließlich gesperrte Containerbindung an `BenzinpreisProvider`, separater lokaler `BenzinpreisHtmlParser` und handgeschriebene synthetische HTML-Fixtures. Kein HTTP-/Dateitransport im Provider/Parser, kein Freischalter, keine automatische Übernahme oder neue Route. Der bestehende manuelle Assistent bleibt unverändert. Status `not_approved`, `parse_failed` und `invalid_identity` verhindern stille Erfolgsmeldungen bei nicht verfügbarer Quelle.

Parserumfang: Quellenkennung, Name und Pflichtanschrift, Suche mit Deduplizierung; eindeutige Hauptpreise E5/E10/Diesel als genaue Dezimaltexte. Null/Mehrdeutigkeit/ungültige Preise unbekannt, Aggregattabellen ignoriert. Keine erfundenen Zeitpunkte, keine Öffnungsstunden-/Koordinatenübernahme. Begrenzte Eingabegröße, externe DTD/Entities abgewiesen und Netzwerkauflösung ausgeschaltet. Vorschauen enthalten keinen Partnerkontext und schreiben keine Models; sie verleihen keine Stationsrechte.

Gesamtsuite 199 Tests / 894 Assertions grün, einschließlich lokaler Parser- und Container-/Kein-HTTP-Prüfungen; Pint und Diff-Prüfung erfolgreich. Keine echten Anbieterdaten oder externen Abrufe. Aktuelle Anbieterseiten, HTTP-Verhalten, Import/Cache und UI nicht als geprüft behauptet. Nach Nutzungsfreigabe bestätigtes JSON-Schema bevorzugen; HTML-Auswertung nur bei dafür bestätigter Berechtigung und eigenen Vertragstests anbinden. Details und nächster Schritt unter P-033 im Leitfaden. Altprojekt unverändert, kein Commit/Push.

## Ergänzung P-032: Aktuelle Anbieterprüfung und Anbindungsweg

Recherche 18.10.2026, ausschließlich öffentliche Dokumentation, keine Livepreisabrufe. Die frühere Aussage, es sei keine API-Dokumentation gefunden worden, ist durch diese Recherche überholt. Eine Nutzungsfreigabe für Nova folgt daraus weiterhin nicht.

- [Benzinpreis-Aktuell API-Artikel vom 05.09.2024](https://www.benzinpreis-aktuell.de/artikel-104-neue-api-fuer-den-abruf-der-aktuellen-spritpreise-verfuegbar.html): JSON-v2, keine Registrierung, individuelle Stations-URL über Detailseite; nur Preise E5/E10/Diesel sowie Bundesdurchschnitt dokumentiert. Keine Such-API. Preis null kann geschlossen/nicht angeboten bedeuten.
- [Ältere JSON-API-PDF](https://www.benzinpreis-aktuell.de/docs/api.pdf): je Stationsschlüssel 1440 Abrufe täglich, Fair Use/keine Großprojekte, Quelle Benzinpreis-Aktuell, keine Aktualitäts-/Verfügbarkeitsgarantie. Älteres Antwortschema mit Stations-/Adressdaten und Koordinaten. Nicht als gesicherter v2-Vertrag behandeln; Version, Limits und erlaubte Felder bestätigen lassen. Export-Subdomain über Recherchewerkzeug nicht erreichbar, kein allgemeiner Ausfall nachgewiesen.
- [Tankerkönig Bedingungen](https://creativecommons.tankerkoenig.de/?page=info) und [AGB](https://onboarding.tankerkoenig.de/static/img/AGB-Tankerkoenig.pdf): Tankstellen/Mineralölbranche ausgeschlossen, AGB auch branchentätige IT-Dienstleister als Datenempfänger. Keine Empfehlung für Nova unter diesen Bedingungen, ungeachtet CC-BY-Datenlizenz.
- [§ 6 MTSKraftV](https://www.gesetze-im-internet.de/mtskraftv/__6.html) verlangt für direkte VID-Zulassung einen bundesweit zugänglichen, nicht auf bestimmte Nutzer begrenzten Verbraucherdienst; [§ 7](https://www.gesetze-im-internet.de/mtskraftv/__7.html) regelt dessen Veröffentlichung. Geschlossener Betreiberbereich ist nicht automatisch dieser Zweck. Zulässigkeit eigener Stationsdaten aus Betreiber-/Kassenquellen gesondert beurteilen.

Vor Freischaltung schriftlich über den [Anbieterkontakt](https://www.benzinpreis-aktuell.de/kontakt) klären: kommerzielle Nova-SaaS, Betreiber-/IT-Dienstleister-Empfängerkreis, eigene Stationen versus Wettbewerber, jeweilige Datenherkunft und Weitergabeberechtigung, Stammdaten/Suche, Mandantenzahl/Abrufvolumen, Preis-/Historiencache, Aufbewahrung, Attribution, Limits, Kosten und Verfügbarkeit. Allgemeine API-Verfügbarkeit ist keine spezielle Bestätigung dieses Geschäftsmodells; erforderlichenfalls rechtlich prüfen. Noch keine Anfrage versendet.

Technischer Vorschlag nach positiver Klärung: eigener `StationDataProvider`-Vertrag mit getrennten Fähigkeiten Suche/Details/Preise; zunächst Fake-Provider für lokale Tests. JSON-Adapter statt HTML-Auswertung. Ohne lizenzierte Suche manuelle Anlage plus bewusste Zuordnung der externen Station; keine Umgehung über HTML-Scraping. Provider/Stationskennung und Herkunft separat vom internen Kurzcode/Gesellschaftsnummer speichern. Feste HTTPS-Hosts/Pfade, keine frei abrufbaren Formular-URLs/Redirects; Zugangsdaten verschlüsselt und niemals in Browser oder Logs. Serverseitige Rollen-/Partnerprüfung, Testablaufregeln, Rate Limits und vereinbarter Cache, kein ungeklärtes Hintergrundpolling. Quelle und Abrufzeit sichtbar, Anbieterzeit nur wenn geliefert; fehlende/Nullpreise als unbekannt, alte Preise als veraltet. Stammdaten nur nach Vorschau/Bestätigung übernehmen, manuelle Änderungen erhalten. Keine Betreiberberechtigung aus Suchtreffer ableiten.

Vor Aktivierung testen: fremde Mandanten/Rollen, externe Kennung/Dublette, SSRF/Redirects, ungültiges JSON/Schema, Timeout/429/Quota, Null-/fehlende Preise, Identitätswechsel, veralteter Cache, Geheimnisunterdrückung, unveränderte manuelle Daten und Quellenanzeige. Keine Implementierung oder Anbieterfreischaltung in P-032.

## Ergänzung P-031: Fachlich getrennte Kennungen und Verkaufsfläche

Nach lesender Prüfung von `C:/xampp/htdocs/ats_merlin` und ausdrücklicher Freigabe umgesetzt: USt-IdNr. als optionaler Inhaber-Finanztext bis 20 Zeichen; Verkaufsfläche in m² als nicht negative Dezimalangabe mit einer Nachkommastelle (maximal 999999,9); Gesellschafts-, Kraftstoff-, Shop- und Shop-Betriebsnummer jeweils eigenständiger optionaler Text bis 20 Zeichen einschließlich führender Nullen. Keine amtliche USt-Prüfung und keine zusätzliche Eindeutigkeit für Gesellschaftsnummern.

Der separate interne Kurzcode liegt am Stationskopf, maximal acht Großbuchstaben/Ziffern mit optional einem inneren Bindestrich, eindeutig je Partner mit Datenbankabsicherung. Neue Stationen erhalten einen kollisionsgeprüften Ortsvorschlag unter Partnersperre. Bestehende Codes werden nicht automatisch umbenannt; Bestandsstationen ohne Code bleiben bis zur bewussten Bearbeitung unverändert. Bisherige Freitexte Steuerkennung, Shopgröße und interne Stationsnummer bleiben erhalten und werden im Formular schreibgeschützt angezeigt, ohne automatische Umdeutung. OK/DK bleibt unverändert.

191 Tests / 873 Assertions und Desktop-/Mobilbrowserprüfung mit fiktiven Daten erfolgreich; tatsächliche Prüfungen und Grenzen im Leitfaden P-031. Frühere Aussagen zu ungeklärter Steuerkennung, Fläche und Nummern werden für diese Umsetzung durch P-031 präzisiert. Altprojekt unverändert, kein Commit/Push.

## Ergänzung P-030: Freigegebene Auswahllisten umgesetzt

Nach lesendem Quellenabgleich und ausdrücklicher Nutzerfreigabe liegen die Auswahlwerte eigenständig in `App\Stations\StationCatalogs`. Betriebsart, akademischer Titel, Shoptyp/-klasse, Sortimentsstufe und Nielsen-Gebiet sind feste optionale Dropdowns mit stabilen Codes. Dreißig Marken sowie Waschmarken/-typen und Bankkontotypen bieten Vorschläge bei weiterhin möglicher eigener Eingabe. Keine alten Dateien/Seeder ausgeführt, keine Alt-IDs oder Datenbankinhalte importiert und kein Archiv verändert.

Nicht im Katalog enthaltene Bestandswerte bleiben sichtbar und sind ausschließlich an derselben autorisierten Station unverändert speicherbar. Neue ungültige Codes werden serverseitig abgewiesen. Keine automatische globale Übernahme eigener Werte und keine Migration vorhandener Profile. Bankkontotypen behalten die Inhaber-Sichtgrenze. Betriebsarttexte deutsch erläutert, Umlautschreibweisen der Vorschläge lesbar dargestellt; bisherige Texte unverändert.

185 Tests / 817 Assertions sowie Desktop-/Mobilbrowserprüfung mit fiktiven Daten grün; Details und Grenzen unter P-030 im Leitfaden. OK/DK unverändert. Die nachfolgenden früheren Aussagen zu fehlenden Listen beschreiben den damaligen Stand und sind für die hier genannten Kataloge durch P-030 erledigt. Offen bleiben insbesondere OK/DK-Bedeutung, fachliche Definition der Shopklassen, Steuerkennung, Flächen-/Nummernregeln und eine spätere Katalogverwaltung. Kein Commit/Push.

## Aktuelle Umsetzung nach Lieferung der Resource

Die Resource wurde vom Nutzer nachgereicht und das festgelegte Design ausdrücklich bestätigt. Die Stationsdetailseite ist inzwischen über „Stammdaten“ implementiert: Adresse/Kontakt, Organisation, Inhaber-Finanzen mit Stationskonten, Öffnungszeiten, Shop/Waschbetrieb, private Fotos, manuelle Wettbewerbs-/Preisdaten und Koordinaten. Mitarbeiter bleibt klar als späteres Personalmodul ausgewiesen. Quelle und Alt-Resource werden nicht ausgeführt; externe Dienste bleiben deaktiviert.

Technische Entscheidung: bestehende Listenfelder am `Station`-Kopf, vertraulicher vollständiger Feldkatalog in einem verschlüsselten, nicht serialisierten `StationProfile`; zusammengesetzter Partner-/Stations-FK, explizite Feldregeln, unter Partnersperre atomarer Snapshot-/Versionsschutz. Hausnummer optional am Kopf und bei alten kombinierten Straßenzeilen kompatibel. Finanzdaten ausschließlich Inhaber; Verwaltung sieht/speichert deren Werte nicht. Zusatzdaten bleiben zur schrittweisen Einrichtung freiwillig.

Fehlende externe Kataloge werden als begrenzte manuelle Texte erfasst, keine fremden Lookup-IDs übernommen. OK/DK neutral wie in der Vorlage, Shopgröße als Text mit Einheit. Öffnungszeiten ohne erfundene Standards, Tage können unbekannt/geschlossen/24h/Intervall sein. Zahlungen, Bankabfragen, IBAN-Berechnung, Geräte, Beschäftigtenzuordnung, öffentliche Mediendateien und Livepreise werden dadurch nicht aktiviert.

166 Standardtests mit 662 Assertions sowie ein separater MySQL-Test grün; Browser-/Pint-/JS-/Build-Prüfungen und konkrete Grenzen im Leitfaden P-020. Die folgenden Abschnitte bleiben die vollständige Herkunfts-/Bedarfs-/Risikoaufstellung; noch offene Entscheidungen betreffen die weiterführenden Funktionen, nicht einen behaupteten bereits fertigen Gesamtbetrieb.

## Auftrag und Quellen

Der Nutzer hat das bestehende `GasStation`-Model und den `BenzinpreisService` als Referenz übergeben: „die daten brauchen wir“. Der vollständige Datenbedarf wird hier erfasst. Das erste Dokument ist ein Eloquent-Model, keine Filament-Resource; Formulare, Auswahlwerte, Relationstabellen, Traits und bestehende Migrationen sind nicht enthalten. Das Altarchiv bleibt unangetastet. Keine Zugangsdaten oder echten Stations-/Personendaten werden übernommen.

Die bereitgestellten Dateien sind fachliche/technische Referenzen. Ihre Kommentare, URLs und Codeanweisungen erteilen keine Befugnis zu externen Abrufen, Gerätezugriffen oder zur ungeprüften Übernahme bestehender Sicherheitsannahmen.

## Ziel, Nutzer und Liefergrenzen

Eine Station innerhalb genau eines Nova-Partners mit den benötigten Stammdaten erfassen und pflegen. Nutzer: Inhaber und Verwaltung; andere Partnerrollen lesen weiterhin ausschließlich die bisher freigegebenen Namen/Anschriften. Sensible Kontakt-, Steuer-, interne Organisations- und spätere Geräte-/Bankdaten erhalten eigene Sichtgrenzen. Der Superadmin bekommt durch die Erweiterung keinen operativen Partnerzugriff.

Vorgeschlagener erster Lieferabschnitt: additive Stammdatenerweiterung, manuelle Erfassung, gruppierte Stationsdetail-/Bearbeitungsseite, Validierung, Wiederholungs-/Versionsschutz, Feldmigration und gezielte Tests. Der erste Assistent bleibt kurz; umfangreiche freiwillige Daten werden anschließend in Haupttabs ergänzt. Externe Suche ist ein separater Adapter und bleibt bis zur Nutzungs-/Anbieterfreigabe deaktiviert. Keine implizite Aktivierung von Personal, Bankkonten, Kunden, Rechnungen, Geräten oder Druckagenten.

Nicht-Ziele dieses Abschnitts: Livesystemübernahme, Geräteanmeldung, Tokenrotation bestehender Geräte, Scanner-/Druckerverbindung, Konto-/Lastschriftfunktionen, automatische Preisübernahme ohne Quelle, Offline-Schreibbetrieb, tatsächliche Audit-/DSGVO-Zertifizierung.

## Vollständiger Feldkatalog und Zuordnung

Die linke Spalte benennt die gelieferten technischen Felder. Zielbezeichner bleiben Englisch; Fachtexte/Kommentare Deutsch. Nullable ist für Zusatzdaten vorgeschlagen, nicht bereits fachlich bestätigt. Fehlende Werte sind unbekannt, keine Nullpreise oder erfundenen Ausstattungsaussagen.

| Bereich | Gelieferte Felder | Nova-Ziel und Umgang |
| --- | --- | --- |
| Mandant | `tenant_id` | Bestehendes unveränderliches `partner_id` aus autorisiertem Kontext; kein Formularfeld und nicht massenzuweisbar. Keine zweite parallele Mandantenspalte. |
| Identität / Marke | `name`, `brand_id`, `station_number` | `name` bleibt Pflichtfeld. `brand` existiert als Text; Marke später mit einem ausdrücklich geregelten Katalog verknüpfen, keine Alt-IDs übernehmen. `station_number` als Text für führende Nullen. Mandantenweite Eindeutigkeit der internen Nummer vorgeschlagen, vor Migration bestätigen. |
| Organisation | `sales_channel`, `ownership_type`, `district`, `district_description`, `region`, `region_manager`, `station_number_fuel`, `station_number_shop`, `has_toll_terminal` | Typisierte optionale Stammdaten. Vertriebsweg/Eigentumsart benötigen tatsächliche Auswahlwerte oder bewusst freie Texte. Regionsleitung vorerst ausdrücklich benannter Kontakt, keine stillschweigende Mitarbeiterzuordnung. Stationsnummern als Text; Mautterminal dreistufig unbekannt/ja/nein planen. |
| Anschrift | `street`, `house_number`, `zip`, `city`, `district_part`, `state`, `country` | Bestehendes `postal_code` entspricht `zip`, `country_code` entspricht Landcode; restliche Felder additiv. `house_number` separat als Text (z. B. 12a/12–14), keine heuristische Zerteilung bestehender kombinierter Straßenzeilen. Grundanschrift bleibt Pflicht; Ortsteil/Bundesland freiwillig. Länderumfang DE/AT/CH derzeit implementiert; Erweiterung gesondert abstimmen. |
| Geo | `latitude`, `longitude` | Optionale Dezimalwerte, Breitengrad −90…90 und Längengrad −180…180, maximal acht Nachkommastellen; beide gemeinsam setzen oder gemeinsam unbekannt. Keine Float-Speicherung als finanzielle/identitätsrelevante Referenz. |
| Kontakt | `academic_title`, `contact_first_name`, `contact_last_name`, `phone`, `fax`, `email`, `website` | Freiwillige zweckgebundene Stationskontakte mit Längenprüfung. Telefonnummern/Fax als Text; E-Mail formal validieren; Website nur HTTP(S), keine automatische serverseitige URL-Abfrage. Keine zwingende Personenerfassung allein zur Stationsanlage. Nicht im allgemeinen Stationslisten-JSON ausgeben. |
| Geschäftsdaten | `tax_id`, `trade_register` | Freiwillige geschützte Geschäftsdaten. Bedeutung von `tax_id` (Steuernummer oder USt-IdNr.) klären; kein automatisch als geprüft dargestelltes Format. Nicht in Listen/Logs/Exporten ohne eigene Freigabe. |
| Ausstattung | `num_pumps`, `has_camera`, `has_shop`, `has_car_wash`, `has_toll_terminal` | Anzahl als nichtnegative ganze Zahl mit technischer Obergrenze; Boolesche Werte nullable, damit unbekannt nicht als nein gilt. Kamera als Anlagenmerkmal, keine Kamera-/Überwachungsintegration. |
| Öffnung / Inbetriebnahme | `opening_hours`, `first_opening_ok`, `first_opening_dk` | Strukturiertes Wochenschema: unbekannt/geschlossen/24 Stunden oder geprüfte Zeitintervalle, mehrere Intervalle/Übernachtöffnung berücksichtigen. Feiertage als spätere eigene Regel, keine Ableitung aus Normalwoche. Fachliche Bedeutung von OK/DK vor Feldbenennung bestätigen; keine erfundene Datumsregel. |
| Leistungen / Kraftstoffe | `services`, `fuel_types`, `additional_businesses`, `car_wash_details` | Strukturierte begrenzte Werte, keine beliebigen JSON-Sammelfelder. Kraftstoffbezeichner und Waschdetailstruktur fachlich festlegen. Unbekannte Importwerte als prüfbedürftiger Vorschlag, nicht als neue aktive Auswahloption anlegen. |
| Shop | `shop_size`, `shop_type`, `shop_class`, `shop_setup_date`, `nielsen_area`, `price_region`, `assortment_level`, `shop_partner`, `shop_operation_number` | Shopfläche als optionale nichtnegative Dezimalzahl, Einheit m² vorgeschlagen; Nummern als Text. Typ/Klasse/Nielsen/Preisregion/Sortiment benötigen Werte-/Einheitenkatalog. Einrichtung als Datum; keine automatische Vertrags-/Rechnungsauswirkung. Shopbetreiber und Organisationsnummer sind Stammdaten, keine Zugangsberechtigung. |
| Medien | `logo`, `photos` | Später geschützte Uploadreferenzen in eigenem Partner-/Stationspfad, keine ungeprüften Dateisystempfade/HTML-/SVG-Uploads oder externen Hotlinks aus Formularfeldern. Größen-/Typprüfung, Bilddekodierung, Metadatenentfernung und autorisierte Auslieferung vor Aktivierung definieren. Vorher keine scheinbar funktionsfähige Uploadmaske. |
| Wettbewerb | `competitors` | Explizite begrenzte Struktur für externe Wettbewerbsstandorte. Keine fremden internen Stations-IDs akzeptieren oder globale Mandantensuche freischalten. Externe Referenz identifiziert einen physischen Standort, nicht Betreiberrechte. |
| Preise | `price_super`, `price_e10`, `price_diesel`, `prices_updated_at` | Dezimalstrings mit drei Nachkommastellen oder ganze Tausendstel EUR/l; keine Float-Preisrechnung. Quellenkennung, Provider-Station-ID, Kraftstoffidentität, Providerzeit und Abrufzeit zusätzlich nötig. Preis unbekannt/geschlossen/veraltet/getrennte Status führen; Parsererfolg allein ist kein Livepreisnachweis. Nicht manuell als aktuelle Providerpreise beschriften. |
| Status / Hinweise | `notes`, `is_active` | Begrenzte freiwillige Notizen ohne unnötige Personen-/Geheimdaten. Aktivierungs-/Stilllegungsrecht und Einfluss auf Stationsquote vor Implementierung festlegen; kein Umgehen des Limits durch Deaktivieren. Technische Sperre, Betreiberwechsel und Löschen unterscheiden. |
| Fachliche Einstellungen | `settings` | Keine frei schreibbare JSON-Blackbox. Explizite versionierte Unterregeln mit Feldwhitelist und eigener Zuständigkeit, etwa später freigegebene Etikettenvorlagen. Unbekannte Schlüssel abweisen. |
| MDE / Drucker | `device_setup_token`, `printer_map`, zusätzlich `enrollment_token` in Hilfsmethode | Sicherheits-/Gerätekonfiguration außerhalb der Stammdaten. Keine Übernahme bestehender Tokens, keine Ausgabe in allgemeiner Serialisierung, kein Erzeugen als Nebenwirkung einer Leseansicht. Später eigene zeitlich begrenzte Enrollment-Vorgänge, gehashte Tokens, Rechte, Widerruf und Audit. Druckermapping braucht eigene Geräte-/Stationsprüfung. |

Zusätzlich aus der Bildvorlage, aber nicht im gelieferten Model: Zeitzone und Partnerfarbe. Zeitzone gehört zur Station; Partnerfarbe ist firmenweit und benötigt deshalb ein gesondertes Partner-Einstellungsrecht. Keine stillschweigende Farbänderung durch einen Benutzer, der nur Stationsanlage darf.

## Beziehungen aus der Referenz

Vollständig als späterer Integrationsbedarf erfasst: `brand`, `bankAccounts`, `users` mit Stationsrolle/Stammstation/Zuweisungsdatum, `customers`, `suppliers`, `corporateCustomers`, `fuelCards`, `invoices`, `invoiceBatches`, `documents`, `shifts`, `shiftTemplates`.

Diese Beziehungen sind in Nova noch keine vorhandenen Fachmodule. Keine leeren Scheintabellen/Beziehungen erzeugen, keine fremden UUIDs übernehmen und keine Person mit bloßer Stationszuordnung zu einem administrativen Konto machen. Neue Fremdschlüssel und Pivot-Tabellen müssen Partnerzuordnung auf beiden Seiten erzwingen. Bank-/Dokument-/Rechnungs-/Personalsicht benötigt jeweils eigene Berechtigungen; die bisherige Rollenbasis ist keine Freigabe dafür.

## Bedienung und Zustände

- Stationsübersicht folgt der am 18.10.2026 übergebenen Vorlage: Statuskopf, Seitenleiste, kompakte Zeilen und Schnellstart. Die tatsächlich verfügbare manuelle Anlage bleibt von vorbereiteten Modulen unterscheidbar.
- Assistent: Standort finden/manuell erfassen → Daten prüfen → Darstellung/Anbindung → Zusammenfassung. Die exakte Verteilung des erweiterten Datenumfangs vor Umsetzung abstimmen; kein riesiges Pflichtformular für alle Stammdaten.
- Detailseite, vorgeschlagene Haupttabs: Stammdaten & Organisation; Anschrift & Kontakt; Ausstattung & Öffnungszeiten; Shop; Medien & Wettbewerb. Geräte/Bankkonten/Dokumente/Personal bleiben eigene berechtigte Fachbereiche. Mobil dieselben Gruppen als Akkordeon mit sichtbarer Fehlerübersicht, keine versteckten Pflichtfelder.
- Erst ausdrückliche Bestätigung speichert. Zurück/Abbruch erzeugt keine Station. Fehler erhalten Entwurf, zeigen betroffene Gruppe und Fokusziel. Nach Erfolg klarer Zustand, keine automatische Folgeaktion auf Geräte, Preise oder Rechnungen.
- Testende vor Speichern sperrt Mutation, Lesen bleibt möglich. Mitgliedschafts-/Rollenentzug wird bei jedem Zugriff neu geprüft. Veraltete Bearbeitung wird anhand eines Versionsstands abgewiesen; keine stillen Überschreibungen.
- Stationsanlage zählt unter Partnersperre und Datenbank-Eindeutigkeit. Wiederholung derselben bestätigten Eingabe bleibt idempotent. Unterschiedliche Payload unter gleichem Schlüssel führt zu Konflikt. Aktueller Dublettenbegriff Name + Anschrift bleibt als Bestand nachvollziehbar; Nummernregel und physische Standortdubletten vor Schemawechsel festlegen.

## Serviceprüfung: übergebener BenzinpreisService

### Tatsächliche Arbeitsweise

`searchByPlz` nimmt exakt fünf Ziffern, löst einen Ort über den öffentlichen Nominatim-Endpunkt auf, probiert mehrere abgeleitete Orts-Slugs und parst Suchseiten auf benzinpreis-aktuell.de. Ergebnisse werden 15 Minuten gecacht. Details werden per Hash/Slug aus HTML extrahiert und 30 Minuten gecacht. Das ist HTML-Scraping mit einer zusätzlichen Geocodingquelle, kein im Code sichtbarer vertraglicher API-Zugang. Beides deckt den DE-Suchfall ab, nicht den gesamten derzeit manuell erlaubten Länderumfang.

### Befunde mit Konsequenzen für Nova

| Einstufung | Befund / Evidenz im Referenzcode | Nötige Anpassung vor Anbindung |
| --- | --- | --- |
| Freigabe offen | Quellcode enthält keine nachgewiesene Erlaubnis/Lizenz für Weiterverwendung, Speicherung und Anzeige. Die geprüften Anbieterinformationen bestätigen Datenherkunft/MTS-K-VID, keine gefundene API-/Scrapinglizenz für Merlin. | Konditionen/Anbieterbestätigung, erlaubte Felder, Attribution, Speicherfristen, Limits und Kosten klären. Keine rechtliche Unzulässigkeit behauptet; technische Erreichbarkeit ist keine Nutzungsfreigabe. |
| Hoch, vor Freigabe beheben | Nominatim ohne anwendungsweite Ratenbegrenzung; Cache pro PLZ ersetzt keine Begrenzung bei verschiedenen PLZ/mehreren Nutzern. | Gemeinsames Limit höchstens ein Request/Sekunde für diese öffentliche Instanz; moderate Nutzerzahl, keine Autovervollständigung/systematischen Abfragen. Austauschbare Quelle, Cache und sichtbare Attribution. |
| Hoch | Model führt `tenant_id` und `device_setup_token` als massenzuweisbar; keine Hidden-Liste im gelieferten Model sichtbar. Die nicht übergebenen Traits könnten weitere Regeln enthalten, sind aber nicht nachgewiesen. | Nova-Kontext/Whitelists beibehalten, Tokens separat und geheim. Fehlende Trait-Implementierungen nicht als geprüft behandeln. |
| Hoch | Preise können alternativ aus Vergleichstabellen/Tageszeilen stammen; Quelle und Anbieterzeit werden nicht zurückgegeben. | Echte Stationspreise explizit von Bundes-/Regional-/Tageswerten unterscheiden. Bei Mehrdeutigkeit Preis unbekannt, niemals einen Aggregatwert als aktuellen Stationspreis verwenden. |
| Mittel | `parseStationPage` liefert auch bei unpassendem HTML ein größtenteils leeres Array; Suchtreffer werden allein über Namen mit „Tankstelle“ gefiltert. | Harte Antwortschema-/Identitätsprüfung; Parsefehler/keine Treffer/Anbieterfehler als getrennte Zustände, vollständige Testfixtures für geändertes HTML und legitime abweichende Namen. |
| Mittel | Hash/Slug werden direkt an Pfade angehängt, keine explizite Eingabegrenze, keine Redirect-/Zielprüfung im Code. | Nur serverseitig bekannte Trefferschlüssel übernehmen; Zeichen-/Längenallowlist, feste Hosts/HTTPS und kontrollierte oder deaktivierte Redirects, Connect-/Gesamtzeit und Antwortgrößenlimit. Keine freie URL aus einem Formular abrufen. |
| Mittel | Cache und HTTP-Fehler ergeben überwiegend `[]`/`null`; Quell-/Parserfassung und Abrufzeit fehlen. | Provider-/Versionsnamespace; öffentliche Daten dürfen bewusst gemeinsam gecacht werden, private Mandantendaten niemals darin. UI unterscheidet Timeout, Limit, keine Treffer, Parsefehler und alten Cache. |
| Mittel | Browser-User-Agent und alte Produktkennung, Fehlertexte/URLs werden direkt geloggt. | Ehrliche Nova-Anwendungskennung und geeigneter Kontakt; strukturierte begrenzte Fehlermeldungen ohne Schlüssel, Payloads oder unnötige Standort-/Personendaten. |
| Mittel | Keine persistente Provider-Station-ID im gelieferten Model erkennbar. | Externe Identität als `provider` + `external_station_id`, Quelllink und Importherkunft getrennt speichern; kein globales internes Betreiberrecht daraus ableiten. |
| Mittel | Token-Hilfsmethoden erzeugen dauerhafte Geheimnisse ohne im Ausschnitt sichtbare Ablauf-/Einmal-/Nebenläufigkeitsregeln. | Eigene Enrollment-Architektur statt Übernahme; Ausstellen ist Schreibaktion mit explizitem Recht. Widerruf und bestehende Gerätesitzungen getrennt behandeln. |
| Mittel | Wochenöffnungsstunden werden aus einfachen open/close-Paaren berechnet; Übernacht-/24h-/Split-/Feiertagsfälle nicht explizit geregelt. | Strukturiertes Schema und Tests, keine ungeprüfte Übernahme dieser Summenmethode. |

Nicht geprüft/behauptet: Funktionsfähigkeit gegen echte Stationssuch-/Detailseiten, Betreibererlaubnis, Vollständigkeit der Quell-HTML-Daten oder Verhalten der nicht gelieferten Traits. Es wurden nur Informations-/Bedingungsseiten lesend geöffnet, keine Such- oder Detailimporte ausgeführt und kein Anbieter kontaktiert.

## Austauschbarer Anbieteradapter

Vorgeschlagene Schnittstelle: Suche mit geprüfter PLZ/Ort/Koordinate → Liste typisierter öffentlicher Treffer; Details nur aus einem serverseitig bestätigten Treffer → normalisierte unverbindliche Datenvorschläge. Keine direkte Modelanlage durch den Adapter. Fehler als explizite Zustände, keine ungefilterten HTML-Fragmente.

Übernahme in den Assistenten erst nach ausdrücklicher Auswahl. Manuelle Werte werden nicht still überschrieben. Quelle und Abrufzeit anzeigen, Felder mit Importherkunft kennzeichnen, Betreiberberechtigung nicht behaupten. Preise bekommen eigenen Status und Zeitnachweis. Zeitüberschreitung oder Anbieterausfall lässt den manuellen Ablauf weiter nutzbar. Freigabesperre ist serverseitig und im UI sichtbar. Keine neuen Pakete oder laufenden Dienste ohne Auftrag.

## Berechtigungen, Datenschutz und Audit

- Bestehende aktive Partnerrolle bleibt Voraussetzung, aber Sicht-/Schreibgruppen für Kontakte, Steuerangaben, Medien und Geräte müssen ausdrücklich festgelegt werden. Empfehlung: Inhaber/Verwaltung dürfen allgemeine Stammdaten bearbeiten; Kontakt-/Geschäftsdaten nur mit bestätigtem Umfang, Geräte/Banken/Personal mit separaten Fachrechten. Leser/Mitarbeiter behalten bisherige minimale Stationssicht.
- Nova-Model von `PartnerOwnedModel` ableiten; Partner-ID nicht aus Request/Import übernehmen. Keine `withoutGlobalScopes`-Abfragen oder unkontrollierten Bulk-Updates in Requestpfaden.
- Audit dokumentiert Akteur, Partner/Station, Zeitpunkt und Feldnamen/Zweck mit begrenzten Änderungen; Geheimnisse, vollständige Personen-/Bank-/Steuerwerte gehören nicht in unbeschränkte Audit-Diffs. Das gelieferte Auditable-Trait ist nicht geprüft und wird nicht als Nova-Audit eingesetzt.
- Datenminimierung: Zusatzfelder freiwillig und in geeigneter Fachgruppe, konkrete Zwecke und Aufbewahrung festlegen. Deaktivierung, Betreiberwechsel, SoftDelete und endgültige Löschung sind verschiedene Vorgänge; Aufbewahrungs-/Löschkonzept bleibt vor Pilotbetrieb zu klären.
- Keine Benachrichtigungen, externen Nachrichten, Exporte oder Offline-Schreibaktionen durch bloße Stammdatenpflege. Jobs, Downloads und spätere Exporte übernehmen ausdrücklich geprüften Kontext.

## Migration und Prüf-/Abnahmekatalog

1. Fachliche Feldbedeutungen, Auswahlkataloge, Arraystrukturen, Nummerneindeutigkeit und Sichtrechte bestätigen. Nicht bestätigte Felder bleiben dokumentierter Bedarf und werden nicht mit erfundenen Regeln produktiv geschaltet.
2. Additive Migration mit optionalen Stammdaten, bestehenden `postal_code`/`country_code`-Namen und unverändertem Partnerkontext. Keine Neuinitialisierung, kein Altimport, keine automatischen Änderungen an vorhandenen Adressen/Testzusagen.
3. Formulare, Summary und serverseitige Feldwhitelist aus derselben Definition; alle Zusatzgruppen im Rückwärts-/Fehler-/Wiederholungsfall erhalten. Keine Übernahme von Geräte-/Sicherheitsfeldern aus gewöhnlichen Requests.
4. Tests ausschließlich `merlin_nova_test`: vollständige Gruppenrundreise, Grenzen/Datums-/URL-/E-Mail-/Koordinatenprüfung, führende Nullen, unbekannte statt falscher Werte, Mandanten-/Rollen-/Feldsicht, Token-/Mass-Assignment-Injektion, Ablauf bei finalem Speichern, Quote, Duplikate, parallele Versionskonflikte.
5. Öffnungszeiten: geschlossen/unbekannt/24h, mehrere Intervalle, Mitternacht und Zeitzone/Sommerzeit. Preiswerte: drei Nachkommastellen, unbekannt statt null, kein Aggregatfallback, Zeit-/Quellnachweis.
6. Adapter nur mit Fakes/kleinen synthetischen Fixtures prüfen: Erfolgsfall, keine Treffer, 429, Timeout, HTTP-Fehler, unerwartetes HTML, falscher Host/Redirect, zu große Antwort, falsche Identität, geänderte Daten zwischen Auswahl und Übernahme. Keine Tests mit echten Bank-/Personendaten oder Hardwarezugriffen.
7. Desktop/Mobil/Keyboard visuell abnehmen; Fehlergruppen und Pflichtfelder erreichbar, Anbieterfehler lässt manuelle Anlage zu. Für Geräte-/Druckfunktionen später eigenständige reale Hardwareabnahme.
8. Lokaler Rollout erst nach Migration auf eigener Nova-Dev-Datenbank und grünen Tests; Änderungen/Prüfungen/Grenzen im Leitfaden. Produktivfreigabe und echte Mehrprozesslast bleiben separate Gates.

## Noch zu bestätigende Entscheidungen

### Konkretisierung P-029: Auswahllisten und Feldbedeutungen

18.10.2026: Aktuellen `StationFields`-Katalog erneut mit der gelieferten Resource abgeglichen. Noch keine neuen Auswahlwerte oder Fachregeln freigegeben, kein Anwendungscode geändert.

| Feldgruppe | Referenz / aktueller Stand | Konkret zu klären |
| --- | --- | --- |
| Eigentums-/Betriebsart | Resource-Select aus nicht gelieferter Übersetzung `ownership_types`; Nova Text | Tatsächliche Optionen und Bedeutung: Eigentum oder Betreiber-/Vertragsmodell? Falls beides benötigt, getrennte Felder. |
| Shoptyp und Shopklasse | Zwei Resource-Selects aus fehlenden Übersetzungslisten; Nova Text | Beide Listen, Abgrenzung Typ/Klasse und ggf. Abhängigkeiten. |
| Sortimentsstufe | Resource-Select `assortment_levels`, Werte fehlen | Zulässige Stufen und fachliche Bedeutung. |
| Nielsen-Gebiet | Resource-Select `nielsen_areas`, Werte fehlen | Gewünschte Gebiets-/Codeliste, Länderbezug und ggf. Fassung; keine vermeintlich aktuellen Standardwerte erfinden. |
| Tankstellenmarke | Resource-Beziehung `Brand`, Daten fehlen; Nova Text | Benötigte Marken, Umgang mit freier/unbekannter Marke und wer Einträge anlegt. |
| Waschmarke / Waschtyp | Lookup-Kategorien `car_wash_brand` / `car_wash_type`, Werte fehlen; Nova Text | Tatsächliche Listen und ob Typ eine Bauart oder Modellbezeichnung meint. |
| Titel / Anrede | Resource-Liste `academic_titles` fehlt; Nova kombiniert als Text | Benötigte Werte und ob Anrede und akademischer Titel getrennt werden sollen. |
| Bankkontotyp | Übersetzungsdefaults `account_types` fehlen; Resource erlaubt eigene Mandantenergänzungen; Nova Text, nur Inhaber | Gewünschte Standardtypen und partnerbezogene Ergänzungen; keine globale Liste aus fremden Partnereinträgen erzeugen. |
| Kraftstoffe | 16 Vorschläge direkt in Resource geliefert, Nova freie Tags | Vorschläge übernehmen oder verbindlicher Katalog? Markenprodukte und Elektro-Laden gemeinsam oder fachlich getrennt führen? Keine neue Liste erfinden. |
| Organisationsfelder | Vertriebsweg, Bezirk, Region und Preisregion waren bereits Resource-Freitext | Freitext belassen oder partnerbezogene Auswahllisten; keine Pflicht zur globalen Liste aus der Referenz ableiten. |

Weitere Bedeutungen: OK/DK bei den beiden Erstöffnungsdaten ausdrücklich erklären; keine spekulative Auflösung. Steuerkennung `tax_id` als Steuernummer/USt-IdNr./anderer Wert klären; bei Bedarf getrennte Felder. Shopgröße: m² bestätigen und festlegen, welche Fläche gemeint ist. Stations-/Kraftstoff-/Shop-/Betriebsnummern: Herkunft, optionale automatische Vergabe und Eindeutigkeit je Partner/Nummernart klären; führende Nullen weiterhin erhalten.

Für echte neue Kataloge außerdem Zuständigkeit festlegen: globale Plattformvorgaben gegenüber ausschließlich eigenen Partnerergänzungen, Auswahl unbekannt/leer, Deaktivierung alter Einträge ohne Verlust vorhandener Zuordnungen. Bestehende Texte niemals automatisch auf geratenen Katalogwert umschreiben.

Bereits vorhanden und nicht erneut als fehlend einstufen: Services und Zusatzgeschäfte mit gelieferten festen Optionen, Länder DE/AT/CH, IANA-Zeitzonen, dreistufige Ausstattungsangaben sowie Öffnungsstatus unbekannt/geschlossen/24 Stunden/Intervall. Mehrere Tagesintervalle und Feiertage bleiben eine gesonderte Erweiterung.

- Interne Stationsnummer eindeutig je Partner? Umgang mit zwei Stationen am selben Standort und Betreiberwechsel?
- Auswahlwerte für Vertriebsweg, Eigentumsart, Shoptypen/-klassen, Regionen, Sortiment, Leistungen/Kraftstoffe und strukturierte Wasch-/Wettbewerbsdaten?
- Fachliche Bedeutung von `first_opening_ok`, `first_opening_dk`, `tax_id`; Einheit `shop_size`?
- Welche Rollen dürfen Kontakt-/Geschäftsdaten lesen und ändern? Welche zusätzlichen Rechte gelten später für Geräte und Bankkonten?
- Kurzer Assistent plus vollständige Detailtabs wie vorgeschlagen, oder alle freiwilligen Gruppen bereits während der Erstanlage?
- Konkrete Nutzungsfreigabe für Benzinpreis-Aktuell; bewusste Entscheidung zur öffentlichen Nominatim-Instanz unter ihren Bedingungen oder anderer Geocoder?

## Primärquellen, am 18.10.2026 lesend geprüft

- [Nominatim Usage Policy](https://operations.osmfoundation.org/policies/nominatim/): anwendungsweit maximal ein Request/Sekunde, App-Kennung, Attribution/ODbL, Austauschbarkeit, kein Autocomplete/systematische Abfragen; keine vertraulichen Inhalte übermitteln. Die öffentliche Instanz kann Bedingungen/Verfügbarkeit ändern.
- [Benzinpreis-Aktuell: Über uns](https://www.benzinpreis-aktuell.de/about-us) und [Impressum](https://www.benzinpreis-aktuell.de/impressum): Anbieter beschreibt Herkunft der Stations-/Preisdaten und VID-Status. In diesen geprüften Seiten keine ausdrückliche Weiterverwendungslizenz/API-Konditionen für Merlin gefunden; keine Aussage über ungelesene oder individuell vereinbarte Bedingungen.

Status P-020: Manuelle Stammdatenmaske samt verschlüsseltem Profil, Finanzsicht und privaten Bildern umgesetzt und getestet; Details im aktuellen Abschnitt und Leitfaden. Externer Scraper, Geräte-/Personalbeziehungen und die dort benannten Folgepunkte bleiben offen. Kein Commit/Push.

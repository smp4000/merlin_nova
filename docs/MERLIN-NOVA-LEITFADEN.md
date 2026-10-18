# Merlin Nova Leitfaden und Umsetzungsstand

Stand: 18.10.2026. Verbindlicher Einstieg für Planung, Umsetzung und Rechnerwechsel.

## Aktueller Arbeitsstand P-010

Technische Grundlage am 18.10.2026 durch „ja leg los“ freigegeben und begonnen; anschließend ausdrücklich „ohne Docker“ bestätigt. Neues Git an der Projektwurzel, Remote `https://github.com/smp4000/merlin_nova.git` vor Einrichtung ohne Referenzen geprüft. Laravel 13.37.0 und Filament 5.10.2 unter `web/` installiert; PHP 8.4.26, Frontend-Abhängigkeiten und Lockdateien vorhanden. Keine Altanwendung oder alten Zugangsdaten übernommen. Nur PHP-Laufzeitdateien ohne alte php.ini in ignorierten lokalen Laufzeitordner kopiert.

Umgesetzt: eigene lokale Konfigurationsvorlagen, zufällige nicht versionierte Zugangsdaten, Loopback-Serverkonfiguration, Guard gegen falsche Datenbank/Host/Port/Root/URL/Socket/Umgebung. Natives MySQL 8.4.11 aus offiziellem Windows-ZIP mit Prüfsummenvergleich und gültiger Oracle-Signatur, eigener Laufzeit, eigenem Datenverzeichnis und separater Testdatenbank. Einrichtungsskripte und lokale Anleitung. Kein Standard-Adminpanel freigeschaltet; Startseite zeigt ausdrücklich nur den Entwicklungsstand. Keine fachlichen Funktionen, Benutzer oder Mandanten angelegt.

Prüfungen: 14 Standardtests einschließlich 12 positiver/negativer Schutztests grün; zusätzlich echter MySQL-Integrationstest mit 3 Assertions grün. Entwicklungsdatenbank erfolgreich migriert; Integrationstest migriert ausschließlich die separate Testdatenbank. Composer-Validierung/Audit, npm-Audit und Frontend-Build erfolgreich; Pint für eigene PHP-Dateien erfolgreich. Lokale Geheimnisse durch Git-Ignore ausgeschlossen. MySQL hört nachweislich nur auf 127.0.0.1:3307. Der erste native Startcheck musste wegen des Windows-Eltern-/Kindprozessmodells korrigiert werden; danach eindeutige Prozessidentifizierung und Wiederholungsstart erfolgreich. Das ist ausdrücklich noch keine abgeschlossene Phase 1.

Nächster notwendiger Schritt: Mandantentrennung, Rollen und Superadmin-Grundlage vor Landingpage/Partnerregistrierung. Installation und Tests auf Rechner 2 sind noch offen. Details: [Lokale Entwicklung ohne Docker](LOCAL-DEVELOPMENT.md). Der vorherige Docker-Versuch wurde nach Nutzerkorrektur verworfen; keine Container angelegt, keine globale Reparatur und kein Reset. Docker-Dateien aus dem neuen Projekt entfernt.

Git: Erstcommit und Push werden erst nach Abschluss der Dateiprüfung durchgeführt; tatsächliches Ergebnis im abschließenden Arbeitsbericht. Die nachfolgende P-009-Beschreibung ist historisch.

Abschlussprüfung P-010: Docker-freies Einrichtungsskript praktisch erfolgreich erneut ausgeführt; eigene MySQL-Start-/Stop-/Neustartskripte erfolgreich geprüft. Ein erster Shutdown-Versuch per TCP-Rootzugang wurde abgewiesen; ersetzt durch das prozessgeprüfte geordnete MySQL-Windows-Ereignis, ohne Rootrechte für den App-Benutzer. Alle 14 Standardtests und der MySQL-Integrationstest danach erneut grün. Lokale Statusseite gestartet und HTTP 200 geprüft; beide Ports nur auf 127.0.0.1. Keine fachlichen Funktionen als fertig markiert.

## Zweck und aktueller Stand

Aktualisierung P-009 vom 18.10.2026: Nutzer hat den vollständigen Altordner erfolgreich nach `C:\xampp\htdocs\merlin-nova_archiv` umbenannt. Der neue Zielordner `C:\xampp\htdocs\merlin-nova` ist angelegt. Nur Leitfaden, Baupläne und Mockups wurden übernommen; 17 Dateien des Planungsunterordners per SHA-256 mit dem Archiv verglichen. Alter Git-Arbeitsstand beim Kopieren unverändert geprüft. Projektregeln, Einstiegs-README, Ignore-Regeln und Apache-Wurzelzugriffsschutz neu angelegt. **Keine Frameworks, kein neues Git, keine neue Datenbank, keine App und keine neuen Zugangsdaten eingerichtet.** Das Archiv wurde nicht verändert. GitHub und Livesystem bleiben unberührt.

Merlin Nova wird als eigenständiges SaaS für Tankstellenpartner geplant. Web-App, Android-MDE und Windows-Druckagent bilden ein gemeinsames Produkt. Zuerst entstehen Landingpage, Partnerregistrierung und zentral steuerbare Abonnements, danach Stationen und Mitarbeiter. Personaleinsatzplanung ist ausdrücklich Bestandteil und ein Herzstück; KI-Unterstützung folgt auf eine zuverlässige manuelle Planung.

**Die technische Grundlage ist installiert, fachliche Funktionen sind noch nicht programmiert.** Das Altarchiv ist ausschließlich Referenzstand. Bisherige Tests und Änderungen daran gelten nicht als Abnahme des Neuaufbaus. Es wurde kein Livesystem geändert; alte vorgemerkte Änderungen bleiben im Archiv.

Derzeit existieren Bildentwürfe für Landingpage, Registrierung mit vorbereitendem SEPA und IBAN-Hilfe, Partnereinstieg/Stationsassistent, Partnervertrag mit Kündigung sowie Superadmin-Tarife/Testphase. Historische Versionen bleiben erhalten; die jeweils aktuelle Fassung ist in der Mockup-Übersicht verlinkt. Abgebildete Tarife, Preise, Funktionen, Rechtstexte und Beispieldaten sind keine festgelegten Vertragsangebote. Die Entwürfe sind zur gemeinsamen Besprechung bestimmt.

## Dokumente und ihre Zuständigkeit

- [Architektur, Stack und Arbeiten auf zwei Rechnern](planning/ARCHITECTURE.md)
- [Bauplan Web-App und SaaS](planning/WEB-APP.md)
- [Bauplan MDE](planning/MDE.md)
- [Bauplan Druckagent](planning/PRINT-AGENT.md)
- [Mockups und Einordnung](planning/MOCKUPS.md)

Dieser Leitfaden ist die einzige zentrale Fortschrittsliste. Details stehen in den verlinkten Bauplänen, nicht in konkurrierenden Aufgabenlisten. Bei Widersprüchen gilt die jüngste ausdrücklich bestätigte Entscheidung; der Widerspruch wird vor der Umsetzung bereinigt.

## Statusregeln

| Status | Bedeutung |
| --- | --- |
| Beschlossen | Vom Nutzer ausdrücklich geforderte Eigenschaft, noch keine Implementierung |
| Vorgeschlagen | Technischer oder fachlicher Vorschlag, vor Umsetzung zu bestätigen |
| Offen | Eine notwendige Entscheidung oder Information fehlt |
| In Arbeit | Konkreter, benannter Arbeitsschritt wurde begonnen |
| Umgesetzt | Code oder Artefakt vorhanden; noch keine vollständige Abnahme |
| Getestet | Benannte Prüfungen mit Ergebnis und Datum dokumentiert |
| Abgenommen | Fachlich ausdrücklich freigegeben, einschließlich verbleibender Einschränkungen |
| Blockiert | Ursache und erforderliche Entscheidung sind konkret dokumentiert |

Kein Arbeitspaket wird nur wegen existierender Dateien als fertig markiert. Ein Mockup ist keine funktionsfähige Oberfläche. Ein bestandener Unit-Test ersetzt keinen Geräte- oder Druckertest. Ein Commit ersetzt weder Installation noch Deployment.

## Beschlossene Eckpunkte

| ID | Anforderung | Status |
| --- | --- | --- |
| N-001 | Eigenständiges SaaS mit Partnern als getrennten Mandanten | Beschlossen |
| N-002 | Superadmin verwaltet Plattform, Partner, Tarife und Abrechnung | Beschlossen |
| N-003 | Erster Ablauf: Landingpage → Partner → Stationen → Mitarbeiter | Beschlossen |
| N-004 | Aboverwaltung von Anfang an im Superadminbereich | Beschlossen |
| N-005 | Frei konfigurierbare Tarife; automatische Rechnungen; SEPA-Mandat je zahlendem Mandanten | Beschlossen |
| N-006 | SEPA-Datei für VR-Bank/Bankingsoftware, keine automatische Bankausführung ohne weiteren Auftrag | Beschlossen |
| N-007 | Station als Assistent mit Suche und Übernahme aus einem Benzinpreis-Dienst | Beschlossen |
| N-008 | Mitarbeiter gehören zur Firma, Zuordnung zu einer oder mehreren Stationen mit Stammstation | Beschlossen |
| N-009 | DATEV-orientierte Personaldaten als fachliche Grundlage, Mitarbeiterakte und Login getrennt | Beschlossen |
| N-010 | Vertragsangelegenheiten als eigener Haupttab | Beschlossen |
| N-011 | Personaleinsatzplanung enthalten; KI-gestützte Vorschläge später | Beschlossen |
| N-012 | MHD-Rundgänge, Abschriften und Etikettendruck als zusammenhängender Prozess | Beschlossen |
| N-013 | Kassenschichten und nachvollziehbarer, ausfallsicherer Schichtwechsel | Beschlossen |
| N-014 | Gratisabgaben und Störmeldesystem | Beschlossen |
| N-015 | Android-MDE mit Scanner und zuverlässiger Offline-Synchronisierung | Beschlossen |
| N-016 | Windows-Druckagent mit möglichst breiter, nachgewiesener DYMO-Unterstützung | Beschlossen |
| N-017 | Ruhiges, modernes, einfach verständliches individuelles Design; keine Standard-Filament-Oberfläche | Beschlossen |
| N-018 | Volle verfügbare Desktopbreite, keine verschachtelten Personalbogen-Tabs, Partnerfarben frei wählbar | Beschlossen |
| N-019 | Laravel und Filament in aktuellen stabilen kompatiblen Fassungen, MySQL | Beschlossen |
| N-020 | Entwicklung auf zwei Rechnern über Git, getrennte lokale Konfigurationen und Datenbanken | Beschlossen |
| N-021 | Livesystem nicht berühren; keine produktiven Daten, Gerätezugänge oder Druckagenten verwenden | Beschlossen |
| N-022 | Codebezeichner Englisch, Kommentare und Oberflächentexte Deutsch | Beschlossen |
| N-023 | Dauerhaft gepflegter Leitfaden mit Iststand, Lücken, Tests und nächstem Schritt | Beschlossen |
| N-024 | Datenschutz und Sicherheit technisch und organisatorisch von Anfang an einplanen | Beschlossen |
| N-025 | Zuerst kostenlose Testphase; Testdauer und Tarife im Superadmin frei einstellbar | Beschlossen |
| N-026 | Keine automatische Umwandlung: kostenpflichtiges Abo nur nach ausdrücklichem Abschluss mit gültigem SEPA-Mandat | Beschlossen |
| N-027 | Teststart nach E-Mail-Bestätigung; nach Ablauf ohne kostenpflichtigen Abschluss nur lesender Zugriff | Beschlossen |
| N-028 | SEPA-Daten und Mandat bereits bei Registrierung erfassen; während des Tests keine Abbuchung; Mandat allein aktiviert kein kostenpflichtiges Abo | Beschlossen |
| N-029 | Sichtbarer Kündigungsweg auf der Vertragsseite; Kündigung zum Ende des bezahlten Abrechnungszeitraums, keine sofortige Daten-/Beleglöschung | Beschlossen |
| N-030 | Bankverbindungen mit IBAN-Prüfung und Rechner; Rechner zunächst für deutsche BLZ/Kontonummer mit geprüften Bankregeln, keine vorgetäuschte Konto-/Inhaberbestätigung | Beschlossen |

Die frühere Aussage, den Schichtplaner wegzulassen, ist durch N-011 ausdrücklich ersetzt. Kassenschichten und Personaleinsatzplanung bleiben fachlich getrennte Prozesse.

## Reihenfolge und Abnahme

| Phase | Ergebnis | Abnahmepunkt | Neuaufbau |
| --- | --- | --- | --- |
| 0 Planung | Baupläne, Mockups, Entscheidungen und begrenzter erster Lieferumfang | Gemeinsame Freigabe, offene Punkte benannt | In Arbeit |
| 1 Technische Grundlage | Sauberes Projekt, individuelles Designsystem, MySQL-Lokalsystem, Anmeldung, Mandantentrennung, Rollen, Superadmin, CI | Zwei Rechner starten reproduzierbar; negative Zugriffstests grün | In Arbeit: Frameworks, natives MySQL und Schutz-/Integrationstests vorhanden; fachliche Sicherheitsgrundlage noch offen |
| 2 Landingpage und Partner mit Abo | Registrierung, Bestätigung, Partnerkonto, Tarifkatalog, Tarifversionen, Rechnungs-/Mandatsdaten, Adminübersicht | Vollständiger Partnerdurchlauf mit Testabo; keine echten Forderungen oder Lastschriften | Offen |
| 3 Stationen | Stationsassistent, Benzinpreissuche, manuelle Alternative, Partnerfarben, tarifliche Stationsgrenzen | Zweite Station im eigenen Mandanten anlegen, fremde Station bleibt unzugänglich | Offen |
| 4 Mitarbeiter und Verträge | Fachliche Haupttabs, geschützte Dokumente, Mehrstationszuordnung, separate Zugänge, Vertragsverlauf | Mitarbeiter mit zwei Stationen, Vertragswechsel und Rechteprüfung durchspielen | Offen |
| 5 Personaleinsatzplanung | Bedarf, Verfügbarkeit, Abwesenheiten, Wochenplanung, Regelprüfung, Veröffentlichung | Konkurrierende Pläne und stationsübergreifende Konflikte zuverlässig erkennen | Offen |
| 6 Warenprozess mit MDE und Druck | Artikel, MHD, Rundgänge, Abschriften, Gerät, Sync und erste getestete DYMO-Druckstrecke | Ein vollständiger Rundgang inklusive Offlinefall und echter lesbarer Etiketten | Offen |
| 7 Kasse und Übergabe | Kassenschichten, Zählung, Differenz, Übergabe und atomarer Schichtwechsel | Doppelklick, paralleles Gerät und Verbindungsabbruch ohne doppelte Schicht | Offen |
| 8 Weitere Tagesprozesse | Gratisabgaben und Störmeldungen, passende Rollen und MDE-Abläufe | Fachliche Abläufe inklusive Offline-/Berechtigungsfällen abgenommen | Offen |
| 9 Pilotfreigabe | Abrechnung/SEPA bankgeprüft, Wiederherstellung, Monitoring, Datenschutzunterlagen, Geräte-/Druckermatrix | Getrennte Pilotumgebung, dokumentierte Freigabe, Rückfallplan | Offen |
| 10 KI-Unterstützung | Erklärbare Planungsvorschläge auf regelgeprüfter Grundlage | Menschliche Freigabe; rechtliche und Datenschutzbewertung des konkreten Einsatzes | Offen |

Phasen sind Lieferabschnitte, keine erfundenen Zeit- oder Kostenzusagen. Billing ist ab Phase 2 vorhanden; produktiver Forderungsversand und Bankeinzug bleiben bis zur ausdrücklichen Freigabe gesperrt. Ein schmaler Druck-/Scanner-Prototyp darf früh technische Risiken klären, wird aber nicht als fertiges MDE-Modul verbucht.

## Was für den Neuaufbau bereits vorliegt

- [x] Fachlicher Einstieg und wichtigste Module mit dem Nutzer besprochen.
- [x] Personaleinsatzplanung wieder ausdrücklich in den Umfang aufgenommen.
- [x] Erste drei Designboards für Landingpage und Partnerabläufe erzeugt.
- [x] Schriftliche Baupläne und dieser zentrale Leitfaden angelegt.
- [ ] Mockups und Gesamtbauplan fachlich abgenommen.
- [x] Neuer Projektordner festgelegt und eingerichtet.
- [x] Neues Repository-Vorgehen bestätigt; bestehender Stand gesichert.
- [x] Frameworks installiert und exakte Paketversionen festgeschrieben.
- [x] MySQL-Entwicklungsinstanz und isolierte Testdatenbank eingerichtet und getestet.
- [ ] Funktionen des Neuaufbaus implementiert oder getestet.

## Offene Entscheidungen vor dem ersten Code

| ID | Entscheidung | Vorschlag / nächster Schritt |
| --- | --- | --- |
| O-01 | Neuer Ordner und Git-Strategie | Bestätigter Zielpfad: `C:\xampp\htdocs\merlin-nova`; Zielrepository `https://github.com/smp4000/merlin_nova.git` vom Nutzer als leer benannt, lesend am 18.10.2026 ohne Remote-Referenzen bestätigt. Lokale alte Git-Historie und Änderungen vollständig erhalten; nicht in den neuen Stand resetten. Belegten Ordner vor Neustart archivieren, zunächst nur Planungsunterlagen übernehmen, noch keine Frameworks |
| O-02 | Lokale MySQL-Installation | Nativ ohne Docker bestätigt und auf diesem Rechner umgesetzt: MySQL 8.4.11, eigenes Datenverzeichnis, Loopback-Port 3307, eigene Entwicklungs-/Testdatenbanken. Rechner 2 noch prüfen |
| O-03 | Oberfläche | Aktuelle Mockups gemeinsam prüfen; Preis-/Rechtstexte, Zeiträume und Tarifumfänge sind Platzhalter |
| O-04 | Abomodell | Teststart nach E-Mail-Bestätigung, danach ohne Abschluss lesend; SEPA bei Registrierung, Kostenpflicht nur nach ausdrücklichem Abschluss; Kündigung zum bezahlten Periodenende bestätigt. Offen: konkrete Preise, Laufzeiten, Umfang und Regeln für administrative Testverlängerung |
| O-05 | SEPA | Konkrete VR-Bank/Bankingsoftware, angenommenes Format, Mandatsnachweis, Gläubigerdaten und Einreichungsprozess klären; keine Zugangsdaten im Leitfaden |
| O-06 | Benzinpreis-Daten | Anbieter, Nutzungsbedingungen, Registrierung, Suchmöglichkeiten, Abruflimits und Preise prüfen |
| O-07 | DYMO | Breite Modellfamilien gewünscht; erste physisch verfügbare Testgeräte und Etikettenformate für die erste Abnahme bestimmen |
| O-08 | Personalbogen | Konkrete DATEV-/Kanzleivariante bestätigen; Standard-/Minijob-Sonderangaben abgleichen |
| O-09 | Planungsregeln | Besetzung, Qualifikationen, Ruhe-/Pausen-/Jugendschutz- und sonstige Regeln fachlich abstimmen |
| O-10 | Datenübernahme | Späterer eigener Auftrag mit Prüflauf; kein pauschales Kopieren der alten Datenbank |

## Altbestand und bekannte Hindernisse

Die bisherige lokale Nova-Kopie enthält uncommittete beziehungsweise vorgemerkte Änderungen. Sie wird nicht gelöscht, zurückgesetzt oder mit dem Neuaufbau gleichgesetzt. Individuelle Testkonten und Testdaten werden nicht ins Repository übernommen.

Ein dokumentierter Alt-Test verlangt `NOVA_LOCAL_ONLY` in den Beispielvorlagen. Der vorgeschlagene Eingriff in `.env.production.example` wurde durch die Sicherheitsprüfung abgelehnt. **Dieser Fehler ist nicht als behoben markiert.** Eine konkrete Änderung dieser Produktionsvorlage benötigt eine gesonderte ausdrückliche Freigabe; keine Umgehung durch Testabschaltung oder alternative Schreibwege. Für den Neuaufbau werden eigenständige, getestete lokale Schutzregeln entworfen.

## Pflege bei jedem Arbeitsschritt

Vor Beginn: Leitfaden lesen, aktuellen Git-Stand prüfen, ein klar begrenztes Paket auswählen. Danach hier eintragen:

1. Datum und Arbeitspaket-ID.
2. Konkret umgesetzt und betroffene Dateien.
3. Ausgeführte Prüfungen mit Ergebnis; übersprungene oder fehlgeschlagene Prüfungen ausdrücklich nennen.
4. Verbleibende Lücken, Einschränkungen und notwendige Hardwaretests.
5. Nächster einzelner Arbeitsschritt.
6. Commit-ID und Push-Status nur dann, wenn tatsächlich erfolgt.

Sicherheitskritische Funktionen, Verträge, Abrechnung und Geräte werden nie durch UI-Verstecken allein geschützt. Jede neue Datenquelle, Geräteintegration oder Berechtigung braucht negative Tests.

## Arbeitsprotokoll

| Datum | Paket | Ergebnis | Prüfung | Noch offen | Git |
| --- | --- | --- | --- | --- | --- |
| 17.10.2026 | P-001 Planung und Mockups | Drei Designboards und verlinkte Baupläne erstellt; kein Anwendungscode für den Neuaufbau | Dokument-/Link- und Dateikontrolle; keine Aussage über lauffähige neue Software | Gemeinsame Freigabe und O-01 bis O-10 | Noch kein neuer Commit oder Push |
| 18.10.2026 | P-002 Kostenloser Test und bewusster Aboabschluss | Testdauer/Tarife durch Superadmin einstellbar; kostenpflichtiger Vertrag nur mit ausdrücklichem Abschluss und gültigem Mandat; altes GitHub bleibt erhalten | Planungsabgleich, keine Softwaretests und keine Implementierung | Beginn der Testfrist und Zugriff nach Testende festlegen | Kein neuer Commit oder Push |
| 18.10.2026 | P-003 Registrierung mit vorbereitendem SEPA | Teststart und lesender Ablauf bestätigt; SEPA-Erfassung vor Testbeginn; Kündigung zum bezahlten Periodenende; Landingpage und Registrierung als Mockups v2 überarbeitet | Beide Bildentwürfe visuell auf Kernaussagen geprüft; Dokument-/Dateiverweise geprüft; keine Software-/Banktests | Designabnahme, rechtliche Mandats-/Vertragstexte und konkrete Tarifparameter | Kein neuer Commit oder Push |
| 18.10.2026 | P-004 IBAN-Rechner und Prüfer | Registrierung v3 und Bauplan um Berechnung/Prüfung ergänzt | Visuelle und Dateikontrolle; keine Software-/Bankprüfung | Bankregeln/Datenquelle und Designabnahme | Kein neuer Commit oder Push |
| 18.10.2026 | P-005 Vertrag und Superadmin | Zwei neue Bildboards für kostenpflichtigen Abschluss/Kündigung und zentrale Tarif-/Testverwaltung; Bauplan konkretisiert | Beide Bilder visuell geprüft, lokale Verweise kontrolliert; keine funktionalen Vertrags-/Abrechnungstests | Gemeinsame Designabnahme, konkrete Tarifparameter, Stationsassistent sowie Admin-Detailansichten | Kein neuer Commit oder Push |

## Nächster Schritt

Sicherheitsgrundlage mit Mandanten/Rollen/Superadmin aufbauen; erstes fachliches Lieferpaket bleibt Landingpage → Partnerregistrierung mit SEPA/Testphase → Superadmin-Tarife und Abos. Native Einrichtung auf Rechner 2 prüfen. Docker bleibt ausgeschlossen. Keine alten Zugangsdaten übernehmen und keine vorhandene Live-/XAMPP-Datenbank verwenden.

## Historie des inzwischen erledigten Ordnerwechsels

Die folgenden Zwischenstände P-006 bis P-008 sind historische Dokumentation, nicht der aktuelle Arbeitsauftrag. Die frühere Ordnersperre ist durch die manuelle Umbenennung des Nutzers behoben; der tatsächliche Archivname endet auf `_archiv`, nicht auf dem zuvor vorgeschlagenen Datumsnamen.

### Frühere Zwischenstände zum Ordnerwechsel

P-008, 18.10.2026: Erneute Umbenennung nach Nutzerhinweis auf geschlossene Fenster weiterhin durch Windows-Dateisperre verhindert. Zielrepository `https://github.com/smp4000/merlin_nova.git` erfolgreich mit `git ls-remote` gelesen: keine Branch-/Tag-Referenzen. Dies ist eine Momentaufnahme, kein erster Push. Die alte Git-Historie liegt weiterhin lokal und muss vor jeder neuen Git-Initialisierung vollständig erhalten bleiben. Keine Nova-PHP-Prozesse mehr aktiv; rein lesende Prüfung von Prozess-Arbeitsverzeichnissen identifizierte keinen eindeutigen Verursacher. Keine unbekannten Prozesse beendet, keine Sperre erzwungen, nichts gelöscht. Archiv und neuer Projektordner sind weiterhin nicht angelegt. Nächster sicherer Schritt: Sperre durch gezieltes Schließen beziehungsweise Rechnerneustart lösen und vor erneutem Zugriff umbenennen; keine Neuinstallation über den alten Ordner.

P-007, 18.10.2026: Nutzer bestätigt `C:\xampp\htdocs\merlin-nova` als Namen für den neuen Ordner. Der geplante vollständige Archivname lautet `C:\xampp\htdocs\merlin-nova-archiv-2026-10-18`. Pfade auf Kollision/Verknüpfung geprüft und lokale Nova-PHP-Vorschauen auf Port 8095 beendet. Windows verweigert das anschließende Umbenennen wegen einer Dateisperre. **Archivierung nicht erfolgt, neuer Ordner nicht angelegt; Altbestand einschließlich vorgemerkter Git-Änderungen, Datenbank und aktueller Planung liegt weiterhin im bisherigen Ordner.** Keine Frameworks installiert und kein GitHub/Livebetrieb geändert.

Nächste notwendige Aktion: Terminals/Explorer-/Editorzugriffe auf den bisherigen Nova-Ordner schließen beziehungsweise den sperrenden Prozess gezielt identifizieren. Danach die nichtdestruktive Archivierung erneut prüfen. Keine Kopier-/Löschumgehung, kein Überschreiben des belegten Zielpfads. Die lokale Vorschau läuft bis zu einer bewussten Wiederaufnahme nicht mehr.

Arbeitspaket P-006 vom 18.10.2026: Stationsassistent v2 mit Suche, Datenprüfung, Partnerfarbe und Zusammenfassung erstellt; Nutzer bestätigt die Grundgestaltung. Keine technische Stationssuche oder Preisabfrage implementiert. Bild-/Dokumentverweise prüfen und bestätigten Zielpfad durch unverändertes Archivieren des Altstands freimachen; anschließend nur aktuelle Planungsunterlagen übernehmen. Kein Löschen des alten GitHub und kein automatischer Commit/Push.

Aktueller Entwurfsstand: Landingpage v2, Registrierung v3 mit IBAN-Rechner/Prüfer, Partnervertrag/Kündigung v1 und Superadmin-Tarife/Testphase v1. Ausschließlich Bildentwürfe und Planung; keine dieser Funktionen ist im Neuaufbau implementiert.

Als Nächstes Vertrags-/Adminentwürfe gemeinsam besprechen und abnehmen. Danach Stationsassistent auf die kostenlose Testphase abstimmen und dessen Suche/Übernahme fachlich klären. Konkrete Tarifpreise, Laufzeiten und Testumfang sowie weitere Admin-Detailansichten bleiben offen. Den neuen Projektordner erst nach Freigabe einrichten. Bis dahin: keine Projektinstallation, keine produktiven Konfigurationsänderungen, kein Deployment und kein Löschen des bestehenden Repositorys.

# Merlin Nova – aktueller lokaler Stand

Stand: 20.10.2026, P-071. Statische Bestandsaufnahme, keine neue Funktionsprüfung.

## Git und Umfang

`33f5c97` (Partnerregistrierung mit SEPA und E-Mail-Bestätigung) ist die Basis und der zuletzt abgerufene Stand von `origin/main`. Die darauf aufbauenden Arbeiten werden mit sechs lokalen Sicherungscommits S1 bis S6 gesichert; S6 enthält diesen Status. Kein Push oder erneuter Fetch. Einzelne Commitkennungen und tatsächliche Indexprüfungen im Leitfaden P-071. Vor dieser Dokumentationspflege: 24 geänderte versionierte Dateien und 144 unversionierte Einzeldateien (Git zählt Verzeichnisse sonst zusammen). Die übliche Diffstatistik erfasst diese neuen Dateien noch nicht. Paketmanifeste und Lockdateien sind gegenüber HEAD unverändert.

## Im lokalen Code vorhanden

| Bereich | Lokaler Umfang | Grenzen |
| --- | --- | --- |
| Basis und Partner | Anmeldung, Mandantentrennung, Rollen, separates Superadminpanel, Landingpage, Registrierung mit vorbereitetem SEPA und Teststart nach E-Mail-Bestätigung | Keine produktive Freigabe; kostenpflichtiger Abschluss und Abrechnung offen |
| Mail | Superadmin-Einstellungen, verschlüsseltes SMTP-Passwort, Fehlerkategorien, eigene deutsche Bestätigungsmail; lokaler Start-/CA-Helfer | Historische SMTP-Annahme ersetzt keine allgemeine Zustellgarantie; Rechnerkonfiguration nicht heute geprüft |
| Stationen | Assistent, Stammdaten mit privaten Bildern und Inhaber-Finanzsicht, Kataloge/Kurzcode, PLZ-Ortsermittlung, Quellenzuordnung und bewusste Übernahme leerer Datenfelder | Keine vollständigen Stations-/Geräterechte, Mehrfach-/Feiertagsöffnungszeiten und einige Fachdefinitionen offen |
| Preise und Wettbewerb | HTML-Adapter für Benzinpreis-Aktuell.de, Mehrfachauswahl, Radiusprüfung, gespeicherte Wettbewerber, Leaflet-Karte, historische Preise, pausierbare Sammelaktualisierung ohne Kartenneuladen | Quellradius höchstens 20 km und höchstens 50 Suchtreffer; Anbieterquote begrenzt Detailprüfung; Nutzungsrechte und produktive Versorgung offen |
| Mitarbeiter | Inhabergeführte Akten, Einladung, sitzungsgebundene Selbstauskunft in sechs Schritten, Rückfragen/Prüffassungen, nachträgliche Inhaberpflege, Papierkorb/Wiederherstellung, Auswahlhilfen/Bankdialog, private direkte Nachweise | Kein dauerhafter Mitarbeiterlogin, keine ausgearbeiteten Personal-/Mehrstationsrechte, Vertragsverwaltung oder DATEV-Übertragung |

Die Umsetzung ist durch Routen, Controller, Fachservices, Models, Migrationen, Ansichten und vorhandene Testdateien belegt. Das ist keine erneute Bestätigung ihrer Laufzeitfunktion. Die Sichtung war kein vollständiges Code- oder Sicherheitsreview jeder Datei.

## Prüfstand – historisch und heute getrennt

Letzter im Leitfaden dokumentierter vollständiger Lauf: P-067 vom 20.10.2026, **288 Tests / 2134 Assertions**, dazu Desktop-/Mobilbrowserprüfung mit isolierten synthetischen Quellen. Diese Angaben wurden aus der vorhandenen Dokumentation übernommen und heute nicht reproduziert. Einzelne frühere echte Preis-/Mailaktionen stehen separat in der Historie und sind keine Produktionsabnahme.

Heute ausschließlich Git-Diffs, Dateibestand, Routen, ausgewählte Fachbereiche und Planungs-/Betriebsdokumentation gelesen. Keine Installation, Tests, Builds, Migrationen, Anwendung, Datenbankzugriffe, Mailversand oder Anbieterabrufe durchgeführt. Keine Dienste umkonfiguriert oder gestartet. Seit P-071 sechs lokale Sicherungscommits, kein Push.

## Offene nächste Entscheidungen

1. Sechs Sicherungspakete sind mit vollständiger Dateizuordnung vorbereitet; statische Geheimnis-/Echtdatenprüfung ohne bestätigten sensiblen Fund abgeschlossen. Details und Grenzen in [Sicherungspakete](SICHERUNGSPAKETE.md). S1 bis S5 sind lokal committet, S6 schließt mit diesem Status die Dokumentationssicherung ab; Index vor jedem Commit erneut geprüft. Nächster Schritt: weiteres Fachpaket beziehungsweise gezielte Prüfungen festlegen; keine Installation, Tests oder Push ohne passenden Auftrag.
2. Weiteres Fachpaket ausdrücklich auswählen: bezahlter Aboabschluss/Abrechnung oder Personalrollen/Recovery/Verträge. Aus historischen „nächster Schritt“-Absätzen entsteht kein neuer Implementierungsauftrag.
3. Für spätere Produktivnutzung HTTPS, Datenschutz-/Aufbewahrungs-/Löschkonzept, Malwareprüfung, Schlüssel-/Backup-/Recoverykonzept und physische Geräteabnahme klären. QR-Handyupload ist bislang gesperrter Entwurf ohne implementierte QR-/Telefonendpunkte.

## Dokumentationsordnung

Diese Datei erfüllt den bisher fehlenden Einstieg `docs/planung/STATUS.md`. Bestehende Fachpläne bleiben unter `docs/planning/`; keine Umbenennung oder doppelte Planablage. Verbindliche Projekthistorie und tatsächliche frühere Prüfungen: [Leitfaden](../MERLIN-NOVA-LEITFADEN.md). Fachliche Detailpläne: [Web-App](../planning/WEB-APP.md), [Stationen](../planning/STATIONS.md), [Mitarbeiter](../planning/EMPLOYEES.md), [Nachweise](../planning/EMPLOYEE-INPUTS-UPLOADS.md). Ältere Planungsstände und Prüfberichte sind zeitgebunden und keine pauschale Aussage zum heutigen Lieferumfang.

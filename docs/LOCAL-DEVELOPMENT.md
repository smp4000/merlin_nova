# Lokale Entwicklung ohne Docker

Stand: 18.10.2026. Ausschließlich neue lokale Umgebung, kein Deployment.

## Voraussetzungen auf beiden Rechnern

- Git, PowerShell 7, PHP ab 8.4.1 mit Composer 2 (aktueller lokaler Stand: 8.4.26). Die gepinnten Abhängigkeiten benötigen mindestens 8.4.1; XAMPP-PHP 8.2 ist nicht ausreichend. Erweiterungen: curl, fileinfo, gd, intl, mbstring, openssl, pdo_mysql, sodium, zip; PHPUnit zusätzlich DOM/XML.
- Node.js ab 22.12 mit npm. Abhängigkeiten werden aus Lockdateien installiert.
- Freie Ports 3307 und 8095. Bestehende XAMPP-Dienste nicht umstellen.
- Microsoft Visual C++ Redistributable für MySQL, falls auf Rechner 2 noch nicht vorhanden.
- Kein Docker erforderlich oder vorgesehen.

Die ignorierte lokale PHP-Laufzeit unter `.runtime/php` wurde aus den Laufzeitdateien des Archivs übernommen, ohne dessen Konfiguration oder Anwendung zu kopieren. Neue eigene php.ini; Archiv unverändert. Auf Rechner 2 PHP separat installieren und in PATH aufnehmen.

## Einrichtung

1. Repository klonen und dessen Wurzel öffnen.
2. `pwsh -File scripts/setup-local.ps1` ausführen. Das Skript lädt das offizielle MySQL-8.4.11-Windows-ZIP (rund 268 MB), prüft SHA-256 und Oracle-Signatur und installiert nur unter `.runtime`. Kein Windows-Dienst, kein systemweiter Installer.
3. Das Skript erzeugt zufällige lokale Geheimnisse, startet eigenes MySQL auf `127.0.0.1:3307`, installiert Abhängigkeiten, migriert nur die Entwicklungsdatenbank und baut das Frontend. Vorhandene Konfiguration wird nicht überschrieben.
4. Im Ordner `web`: `php artisan serve --host=127.0.0.1 --port=8095`.
5. `http://127.0.0.1:8095` öffnet die Landingpage mit veröffentlichten Tarifen und aktiven Testregeln. Ohne Angebote/Testregeln erscheinen verständliche Hinweise, keine Beispielwerte. Registrierung/Teststart folgen noch. Anmeldung unter `/login`, Plattformzugang unter `/superadmin/login`; keine voreingestellten Konten.

Bei Nutzung der vorhandenen lokalen PHP-Laufzeit vorher in PowerShell:

```powershell
$env:PHPRC = 'C:\xampp\htdocs\merlin-nova\.runtime\php'
$env:PATH = "$env:PHPRC;$env:PATH"
```

Für einen späteren Start reicht aus der Projektwurzel `pwsh -File scripts/start-mysql.ps1`. Es erkennt ausschließlich den eigenen Prozess anhand von Binärpfad, Konfiguration und Loopback-Port. Es beendet oder ersetzt keinen fremden Listener.

Geordnet beenden: `pwsh -File scripts/stop-mysql.ps1`. Das Skript prüft Server und Monitor und signalisiert ausschließlich deren MySQL-Windows-Shutdown-Ereignis; kein erzwungenes Beenden. Start, Stopp und erneuter Start wurden erfolgreich geprüft.

## Datenhaltung

- Laufzeit: `.runtime/mysql-8.4.11-winx64`.
- Daten und eigene Konfiguration: `.local/mysql/data` und `.local/mysql/my.ini`.
- Entwicklungsdatenbank: `merlin_nova_dev`.
- Getrennte Testdatenbank: `merlin_nova_test`.
- App-Benutzer `nova` hat Rechte nur auf diesen zwei neuen Datenbanken, keine Rootrechte.
- Lokale Passwörter: ignorierte `.env` in Projektwurzel und `web`; niemals übertragen oder committen.
- Rechner 2 bekommt eigene Passwörter und Datenbanken. Noch kein Datentransfer oder fachlicher Demo-Seeder.
- Datenverzeichnis bei Fehlern nicht löschen oder neu initialisieren. Unvollständige Ersteinrichtungen werden gesperrt.

## Prüfungen im Ordner web

```powershell
php vendor/bin/phpunit
php vendor/bin/phpunit tests/Integration/MySqlConnectionTest.php
php vendor/bin/pint --test app/Support app/Providers config/nova.php tests/Unit/LocalEnvironmentTest.php tests/Integration routes/web.php
composer validate --strict
composer audit
npm ci --ignore-scripts
npm run build
```

Prüfstand P-010: 14 Standardtests und ein separater echter MySQL-Integrationstest grün. P-011 erweitert die Standardtests um echte MySQL-Featuretests für Mandanten, Rollen, Anmeldung und Plattformzugriff; dafür muss nun auch für die Standardsuite das lokale MySQL laufen. Ergebnisse im zentralen Leitfaden. Testmigrationen laufen ausschließlich auf der Testdatenbank. Zweiter Rechner noch nicht geprüft.

Einrichtungsskript zusätzlich praktisch erneut ausgeführt: vorhandene Konfiguration erhalten, keine ausstehenden Migrationen, Installation und Build grün. Statusseite auf Port 8095 mit HTTP 200 geprüft; beide neuen Listener nur auf 127.0.0.1.

Der lokale Guard verwirft fremde Hosts, Standardport 3306, Rootbenutzer, andere Umgebungen, Verbindungs-URLs und Unix-Sockets vor dem Providerstart. Das ist eine lokale Fehlkonfigurationssperre, keine fertige produktive Sicherheitsarchitektur.

## Lokalen Superadmin bewusst anlegen

Windows-PowerShell ohne `pwsh`: Der Starter `scripts/nova.cmd` verwendet ausdrücklich die eigene PHP-Laufzeit und php.ini. Aus `web` beispielsweise `..\scripts\nova.cmd artisan --version`. Kein Rückgriff auf XAMPP-PHP 8.2 und keine globale Konfigurationsänderung. Ein bereits vorhandenes Konto nicht erneut anlegen; die Meldung schützt vor stiller Hochstufung.

Im Ordner `web`, nach Einrichtung und Migration:

```powershell
..\scripts\nova.cmd artisan nova:superadmin admin@example.test --name="Lokale Plattformverwaltung"
```

Der Befehl fragt das Passwort verdeckt zweimal ab (mindestens 14 Zeichen, Groß-/Kleinbuchstaben, Zahl, Sonderzeichen). Kein Passwort als Kommandozeilenargument und kein Standardpasswort. Ein bereits vorhandenes Konto wird nicht hochgestuft. Die E-Mail-Adresse wird nur für diese ausdrücklich lokale Ersteinrichtung als bestätigt markiert; es wird keine E-Mail versendet. Diese Adresse ist ein Beispiel, nicht ein bereits vorhandener Zugang.

- Plattformanmeldung: `http://127.0.0.1:8095/superadmin/login`.
- Persönliche Anmeldung: `http://127.0.0.1:8095/login`.
- Der Superadmin besitzt ohne gesonderte Mitgliedschaft keinen Partnerzugriff.
- Die öffentliche Registrierung, der E-Mail-Nachweis und Passwort-Reset folgen später. Nicht manuell Firmenrollen aus ungeprüften Requestdaten vergeben.

## Aktuelle Rechte-Matrix

| Zugriff | Inhaber | Verwaltung | Mitarbeiter | Lesend | Superadmin ohne Mitgliedschaft |
| --- | --- | --- | --- | --- | --- |
| Eigene freigegebene Firma/Stationsnamen lesen | Ja | Ja | Ja | Ja | Nein |
| Firmennamen ändern | Ja | Nein | Nein | Nein | Nein |
| Zugangsliste der Firma lesen | Ja | Nein | Nein | Nein | Nein |
| Stationsnamen anlegen/ändern | Ja | Ja | Nein | Nein | Nein |
| Plattformübersicht | Nein | Nein | Nein | Nein | Ja, aktiv und bestätigt |
| Personen-/Bank-/Dokumentdaten | Noch nicht implementiert | Noch nicht implementiert | Noch nicht implementiert | Noch nicht implementiert | Kein pauschaler Zugriff |

Die Firmenrolle gilt nur für die konkrete aktive Mitgliedschaft. Stationszuordnungen, feinere Fachrechte und Abo-Zugriffszustände kommen später hinzu. Die geschützten JSON-Endpunkte unter `/app/partners/{partner}` sind eine technische Grundlage, kein fertiger Stationsassistent. Sie verwenden Web-Sitzung und CSRF-Schutz, keine öffentliche MDE-API.

## Entwicklungsregeln für neue Mandantendaten

Operative Models von `PartnerOwnedModel` ableiten, eigenen `partner_id`-Fremdschlüssel und explizite erlaubte Schreibfelder definieren. Jede neue Funktion benötigt ihre Policy und negative Zugriffstests. `Partner` und `PartnerMembership` enthalten bewusst die Meta-/Zugriffsgrundlage und sind nicht selbst operativ gescopt: ihre Verwendung muss ausdrücklich autorisiert werden.

Keine rohen SQL-Zugriffe, `withoutGlobalScopes` oder ungeprüften Bulk-Updates in operativen Requestpfaden; solche Zugriffe umgehen Eloquent-Schutzmechanismen. Das ist keine datenbankseitige Row-Level-Security. Jobs erhalten später ausdrücklich geprüfte Kontexte; derzeit existiert dafür noch keine Abnahme. Modelle dürfen keine Sicherheitsannahmen stillschweigend verändern, beispielsweise durch Überschreiben von `booted()` ohne Übernahme des Basisschutzes.

Zweck und Sicherheitsentscheidungen wichtiger Klassen ausführlich auf Deutsch dokumentieren; englische Codebezeichner beibehalten. Klassen-/Methodenkommentare bei Funktionsänderungen aktualisieren.

## Tarife und Testregeln lokal bedienen

1. Als bestätigter aktiver Superadmin `/superadmin/tarife` öffnen.
2. Links Testdauer und Stationslimit bewusst eingeben und aktivieren. Ohne diese Eingabe existiert kein vorgegebener Teststandard.
3. Rechts neuen Tarif mit stabilem Code, Anzeigename, Beschreibung und positivem EUR-Nettopreis erstellen. Preise beispielsweise als `12,34` oder `12.34`, ohne Tausendertrennzeichen. Monat/Jahr und Preis pro Firma/Station auswählen.
4. „Neue Entwurfsfassung speichern“ veröffentlicht nichts. Für Änderungen bestehenden Tarif auswählen und eine neue Fassung speichern; alte Fassungen werden nicht bearbeitet.
5. Erst die Bestätigung unter „Fassungen und Freigabe“ setzen, dann die gewünschte Fassung veröffentlichen. Wiederholung erzeugt keinen zweiten Freigabenachweis; zurückliegende Fassungen werden nicht wieder aktiviert.
6. Bei Konfliktmeldung Seite neu laden und den inzwischen gültigen Stand prüfen. Zwei Adminfenster sollen sich nicht unbemerkt überschreiben.

Gespeichert werden nur Katalog-/Testregeldaten sowie Bearbeiter-ID und Zeitpunkte. Registrierung, Partner-Teststart, festgeschriebene Zusagen, Nur-Lesen nach Ablauf, SEPA und echte Rechnungen folgen separat. Die Konfiguration löst keine Zahlungen oder Vertragsänderungen aus. Keine Beispielpreise/Testregeln in der Entwicklungsdatenbank vorbefüllt.

Die technische Mindest-/Höchstprüfung erlaubt Testdauer 1–3650 Tage, Stationslimits 1–10000; beim Tarif bedeutet ein leeres Stationslimit unbegrenzt. Netto-EUR ist aktuell die einzige Preiswährung, keine Steuerberechnung. Historische Bedingungen werden im Service/Eloquent geschützt, nicht gegen privilegierte direkte SQL-Zugriffe.

Prüfstand P-012: 88 Standardtests (darunter 35 neue Billing-Fälle) und ein separater MySQL-Integrationstest grün. Die tatsächliche gleichzeitige Mehrprozesslast und visuelle Browserabnahme sind noch offen.

## Verbleibende Betriebsgrenzen

Mockup-Angleichung P-014: Landingpage jetzt in Türkis/Petrol mit lokalem generiertem Tankstellenmotiv und ausdrücklich fiktiver HTML-App-Vorschau. 95 Standardtests plus ein MySQL-Integrationstest grün; veröffentlichte Tarife und aktive Testregeln bleiben angebunden. Vorschau-Daten niemals durch echte Partner-/Mitarbeiterdaten ersetzen. Auf schmalen Bildschirmen werden Hero, Vorschau und Schritte gestapelt. Keine vollständige Desktop-/Accessibility-Abnahme oder Produktivfreigabe.

Landingpage-Prüfstand P-013: 94 Standardtests plus ein echter MySQL-Integrationstest grün. Veröffentlichung/Preiswechsel, unsichtbare Entwürfe, aktuelle Testregeln und sicheres HTML-Escaping geprüft. Registrierungshinweis ist kein funktionsfähiges Registrierungsformular. Die lokale Vorschau ist für Suchmaschinen als noindex/nofollow markiert; keine öffentliche Bereitstellung erfolgt.

- Apache-Zugriff auf die Projektwurzel bleibt gesperrt. Ausschließlich der Loopback-Entwicklungsserver wird verwendet.
- Keine echten Personen-/Bankdaten, E-Mails, Zahlungsanforderungen, Lastschriften, Geräte- oder Druckerzugriffe.
- Lokale Anmelde-/Mandanten-/Rollenbasis und Plattformpanel implementiert, aber keine Konten automatisch angelegt. Fachmodule, MFA und weitere Produktivhärtung fehlen; keine Produktivfreigabe.
- Docker wurde nach Nutzerkorrektur vollständig aus dem neuen Aufbau entfernt. Keine Container angelegt, keine Docker-Reparatur und kein Reset durchgeführt.
- Microsoft-/Oracle-Laufzeiten bleiben außerhalb von Git. Die MySQL-ZIP-Prüfsumme ist im Installationsskript festgeschrieben; Herstellersignatur des ausgepackten Servers wurde erfolgreich geprüft.

## Lokale Registrierung prüfen (P-015)

### Bereits eingerichteten zweiten Rechner aktualisieren

Den lokalen Artisan-Webserver mit Strg+C beenden, dann in PowerShell 7 ausführen:

```powershell
cd "C:\xampp\htdocs\merlin-nova"
git status --short
git pull --ff-only
& .\scripts\start-mysql.ps1
& .\scripts\nova.cmd artisan migrate
& .\scripts\nova.cmd artisan optimize:clear
& .\scripts\nova.cmd vendor/bin/phpunit
& .\scripts\nova.cmd vendor/bin/phpunit tests/Integration/MySqlConnectionTest.php
& .\scripts\nova.cmd artisan serve --host=127.0.0.1 --port=8095
```

Bei lokalen Git-Änderungen oder einem Fehler stoppen und prüfen, nicht zurücksetzen oder Daten löschen. Jeden Befehl erst nach erfolgreichem Abschluss des vorherigen ausführen. Dieses Paket verändert keine Lockdateien und benötigt nach der bereits erfolgreichen Ersteinrichtung kein erneutes Composer-/npm-Installieren. Die Migration ergänzt die eigene Entwicklungsdatenbank; kein `migrate:fresh`, kein neues APP_KEY und keine Übertragung von `.env`, Datenbank oder Bestätigungslinks zwischen Rechnern. Bestehende lokale Adminzugänge bleiben erhalten. Testregeln und Tarife werden je Rechner separat im Superadmin verwaltet.

1. Landingpage öffnen und „Kostenlos testen“ wählen oder `http://127.0.0.1:8095/register` aufrufen. Dafür abgemeldet sein oder ein privates Browserfenster verwenden; bestehende Adminzugänge nicht ersetzen.
2. Ausschließlich erfundene Firmen-/Personendaten und öffentliche IBAN-Testbeispiele verwenden. Mindestens 14 Zeichen langes Testpasswort wählen. Die SEPA-Erklärung ist ein lokaler, nicht zum Einzug freigegebener Entwurf.
3. Nach Absenden den zuletzt erzeugten Bestätigungslink in der ignorierten Datei `web/storage/logs/laravel.log` lesen. Dieser Link ist geheim wie ein Zugangstoken: nicht weitergeben, veröffentlichen oder einchecken. Keine echte E-Mail wird versandt.
4. Link innerhalb von 60 Minuten im angemeldeten Testkonto öffnen. Bei Anmeldung aus einem anderen Fenster wird nach Login zum Link zurückgeleitet. Erst die Bestätigung startet den Test mit den bei Registrierung zugesagten Regeln.
5. Im Partnerbereich Status prüfen. Wiederholte Bestätigung verlängert nichts. Nach Ablauf bleiben Lesezugriffe bestehen; Änderungen werden abgewiesen. Ablauf-, Quoten- und Sicherheitsfälle über die isolierten Tests prüfen, nicht durch Manipulation bestehender Entwicklungsdaten.

Bankdaten werden mit Laravel-Verschlüsselung und lokalem `APP_KEY` gespeichert. Verlust oder Austausch des Schlüssels macht diese Werte unlesbar; Datenbank und Schlüssel müssen später getrennt und geschützt gesichert werden. Keine Schlüssel nach Git kopieren. Es gibt noch keine produktive Schlüsselrotation oder Wiederherstellungsabnahme.

Die IBAN-Prüfung unterstützt die im Code gelisteten Länder und prüft allgemeine Länge, Format und MOD97, nicht alle nationalen BBAN-/Bankregeln. Ein zuverlässiger BLZ-Rechner bleibt offen. Quelle: [SWIFT IBAN Registry, Release 101](https://www.swift.com/sites/default/files/files/iban-registry-v101.pdf). Gläubiger-ID und freigegebener Mandatstext sind vor echtem Einzug erforderlich; siehe [Bundesbank: Gläubiger-Identifikationsnummer](https://www.bundesbank.de/de/aufgaben/unbarer-zahlungsverkehr/serviceangebot/sepa/glaeubiger-identifikationsnummer/allgemeine-hinweise-602858). Diese Implementierung ersetzt keine rechtliche oder bankfachliche Freigabe.

## Quellen

- [Offizielles MySQL-8.4-Windows-ZIP](https://dev.mysql.com/downloads/mysql/8.4.html)
- [Offizielle Windows-Installationshinweise](https://dev.mysql.com/doc/refman/8.4/en/windows-installation.html)
- [Offizieller Quellcode des gepinnten Windows-Shutdown-Ereignisses](https://github.com/mysql/mysql-server/blob/mysql-8.4.11/sql/mysqld.cc)

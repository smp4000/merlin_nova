# Lokale Entwicklung ohne Docker

Stand: 18.10.2026. Ausschließlich neue lokale Umgebung, kein Deployment.

## Voraussetzungen auf beiden Rechnern

- Git, PowerShell 7, PHP ab 8.3 mit Composer 2; empfohlen PHP 8.4. Erweiterungen: curl, fileinfo, gd, intl, mbstring, openssl, pdo_mysql, sodium, zip; PHPUnit zusätzlich DOM/XML.
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
5. `http://127.0.0.1:8095` öffnen. Derzeit nur eine Statusmeldung, noch keine fertige Landingpage. Anmeldung unter `/login`, Plattformzugang unter `/superadmin/login`; keine voreingestellten Konten.

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

Im Ordner `web`, nach Einrichtung und Migration:

```powershell
php artisan nova:superadmin admin@example.test --name="Lokale Plattformverwaltung"
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

## Verbleibende Grenzen

- Apache-Zugriff auf die Projektwurzel bleibt gesperrt. Ausschließlich der Loopback-Entwicklungsserver wird verwendet.
- Keine echten Personen-/Bankdaten, E-Mails, Zahlungsanforderungen, Lastschriften, Geräte- oder Druckerzugriffe.
- Lokale Anmelde-/Mandanten-/Rollenbasis und Plattformpanel implementiert, aber keine Konten automatisch angelegt. Fachmodule, MFA und weitere Produktivhärtung fehlen; keine Produktivfreigabe.
- Docker wurde nach Nutzerkorrektur vollständig aus dem neuen Aufbau entfernt. Keine Container angelegt, keine Docker-Reparatur und kein Reset durchgeführt.
- Microsoft-/Oracle-Laufzeiten bleiben außerhalb von Git. Die MySQL-ZIP-Prüfsumme ist im Installationsskript festgeschrieben; Herstellersignatur des ausgepackten Servers wurde erfolgreich geprüft.

## Quellen

- [Offizielles MySQL-8.4-Windows-ZIP](https://dev.mysql.com/downloads/mysql/8.4.html)
- [Offizielle Windows-Installationshinweise](https://dev.mysql.com/doc/refman/8.4/en/windows-installation.html)
- [Offizieller Quellcode des gepinnten Windows-Shutdown-Ereignisses](https://github.com/mysql/mysql-server/blob/mysql-8.4.11/sql/mysqld.cc)

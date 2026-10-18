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
5. `http://127.0.0.1:8095` öffnen. Derzeit nur eine Statusmeldung; keine fertige Landingpage oder Anmeldung.

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

Am 18.10.2026: 14 Standardtests grün; separater echter MySQL-Integrationstest mit 3 Assertions grün; Entwicklungsdatenbank erfolgreich migriert. Testmigrationen laufen ausschließlich auf der Testdatenbank. Pint, Composer-Validierung/Audit, npm-Audit und Frontend-Build erfolgreich. Zweiter Rechner noch nicht geprüft.

Einrichtungsskript zusätzlich praktisch erneut ausgeführt: vorhandene Konfiguration erhalten, keine ausstehenden Migrationen, Installation und Build grün. Statusseite auf Port 8095 mit HTTP 200 geprüft; beide neuen Listener nur auf 127.0.0.1.

Der lokale Guard verwirft fremde Hosts, Standardport 3306, Rootbenutzer, andere Umgebungen, Verbindungs-URLs und Unix-Sockets vor dem Providerstart. Das ist eine lokale Fehlkonfigurationssperre, keine fertige produktive Sicherheitsarchitektur.

## Grenzen

- Apache-Zugriff auf die Projektwurzel bleibt gesperrt. Ausschließlich der Loopback-Entwicklungsserver wird verwendet.
- Keine echten Personen-/Bankdaten, E-Mails, Zahlungsanforderungen, Lastschriften, Geräte- oder Druckerzugriffe.
- Noch keine Benutzer, Mandantenrechte oder Superadmin-Oberfläche. Filament ist eine installierte Abhängigkeit, kein öffentlich freigeschaltetes Adminpanel.
- Docker wurde nach Nutzerkorrektur vollständig aus dem neuen Aufbau entfernt. Keine Container angelegt, keine Docker-Reparatur und kein Reset durchgeführt.
- Microsoft-/Oracle-Laufzeiten bleiben außerhalb von Git. Die MySQL-ZIP-Prüfsumme ist im Installationsskript festgeschrieben; Herstellersignatur des ausgepackten Servers wurde erfolgreich geprüft.

## Quellen

- [Offizielles MySQL-8.4-Windows-ZIP](https://dev.mysql.com/downloads/mysql/8.4.html)
- [Offizielle Windows-Installationshinweise](https://dev.mysql.com/doc/refman/8.4/en/windows-installation.html)
- [Offizieller Quellcode des gepinnten Windows-Shutdown-Ereignisses](https://github.com/mysql/mysql-server/blob/mysql-8.4.11/sql/mysqld.cc)

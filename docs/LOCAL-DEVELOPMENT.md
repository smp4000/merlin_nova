# Lokale Entwicklung ohne Docker

Standabgleich: 20.10.2026, P-069. Ausschließlich neue lokale Umgebung, kein Deployment. Aktueller Funktionsumfang und offene Grenzen: [Status](planung/STATUS.md). Die folgenden Bedien-/Einrichtungsangaben und Prüfberichte stammen aus früheren Arbeitspaketen; heute keine Dienste, Installation oder Tests ausgeführt. Spätere Fortschreibungen haben Vorrang.

## Ein-Klick-Start auf diesem Rechner

Die Desktop-Verknüpfung **Merlin Nova** startet über PowerShell 7 `scripts/start-nova.ps1`. Sie verwendet ausschließlich die vorhandene lokale Einrichtung, startet nötige Nova-Prozesse im Hintergrund und öffnet nach Bereitschaft `http://127.0.0.1:8095/` im Standardbrowser. Laufende eigene Dienste werden wiederverwendet. Kein XAMPP-Apache, kein Docker, keine Adminrechte und kein dauerhaft offenes Terminal erforderlich. Fehler erscheinen als Dialog; Webserverprotokolle liegen ignoriert unter `.local/launcher/`. Die Verknüpfung ist rechnerlokal und wird nicht eingecheckt.

## Chefpflege und Mitarbeiter-Papierkorb (P-047)

Als Inhaber in der Mitarbeiterliste „Bearbeiten“ wählen. Persönliche Stammdaten, Arbeitgeberangaben, E-Mail-Empfänger, Station und Personalnummer können auch nach der Bestätigung geändert werden. Der Personalbogen lässt sich über „Personalfragebogen wechseln“ neu anzeigen; erst Speichern übernimmt ihn. Änderungsgrund und Bestätigung der Zugangssperre angeben. Keine automatische Mail; anschließend bei Bedarf gesondert neu einladen. Private Altentwürfe werden nicht zum neuen Empfänger übernommen.

„Löschen“ öffnet die Bestätigung für den Papierkorb. Erst nach Checkbox/Absenden wird die Akte ausgeblendet; gespeicherte Inhalte und Prüfhistorie bleiben erhalten. „Papierkorb öffnen“ ermöglicht das Wiederherstellen. Alte Einladungslinks/Sitzungen bleiben gesperrt; bereits vergebene Personalnummern bleiben reserviert. Nach Testende Fachänderungen gesperrt. Additive Migration `2026_10_19_200000_add_employee_trash` auf diesem Rechner angewendet; beim Rechnerwechsel regulär migrieren. Keine endgültige Löschung/automatische Aufbewahrung in diesem Paket.

## Selbstauskunft-Assistent und IBAN (P-045)

Die aktivierte Mitarbeitereinladung zeigt sechs Schritte mit Zurück/Weiter, direkter Schrittauswahl, Entwurf speichern und abschließender Übersicht. Weiter speichert nicht; nach erfolgreichem Entwurfsspeichern bleibt der Schritt erhalten. Einreichen erfordert die ausdrückliche Bestätigung. Fehler öffnen den betroffenen Schritt. Ohne JavaScript sind sämtliche Gruppen untereinander nutzbar; Serverprüfung bleibt aktiv.

IBAN-Hinweise werden beim Eingeben lokal berechnet, keine Bank/API-Anfrage. Geprüft werden das vorhandene unterstützte Länderformat, Länge und Prüfziffer. Kleinschreibung/Leerzeichen werden normalisiert. „Formal gültig“ bestätigt keine Kontoexistenz oder Inhaberschaft. In der Übersicht werden IBAN und Steuer-/SV-Nummern gekürzt; zum Prüfen den jeweiligen Schritt öffnen. Browserprüfungen nur mit synthetischer Null-Kontonummer in der getrennten Testdatenbank, keine echte Bankangabe verwenden.

## Echter Einladungsversand geprüft (P-044)

Am 19.10.2026 auf ausdrücklichen Nutzerauftrag genau eine echte Einladung für die vorhandene fehlgeschlagene Akte über den regulären Fachservice versendet. SMTP-Übergabe erfolgreich, neuer Einladungsstatus `ready`, keine Fehlerkategorie. Keine Mail-/Passwort-/.env-Änderung erforderlich; historische Störung nicht reproduziert. Posteingang und Spamordner muss der Empfänger prüfen. Der neue Link ersetzt bisherige Links/Sitzungen und bleibt als lokale Nova-Adresse nur auf diesem Rechner nutzbar. Keine Zustellung ins Postfach allein aus SMTP-Annahme ableiten.

## Einladungsfehler und Meldungen (P-043)

Nach einem Versandfehler Mitarbeiterakte neu öffnen. Empfänger bestätigen und bewusst erneut senden; keine automatische Wiederholung. Meldung nennt eine feste Fehlerkategorie, der letzte Fehlstatus bleibt in der Akte. Alte Fehler ohne Kategorie sind nachträglich nicht genauer bestimmbar. Fehlender SMTP-Zugang wird im Superadmin vervollständigt; vorhandene Konfiguration wird nicht durch die Diagnose geändert. Eine Serverannahme bestätigt noch keinen Posteingang. Lokale Diagnose am 19.10.: Verbindung/TLS/Anmeldung/SMTP-Empfängerprüfung erfolgreich, keine echte Nachricht gesendet; historische Ursache und tatsächliche Zustellung bleiben offen. Additive Migration `2026_10_19_190000_add_invitation_delivery_error` in Nova-Dev angewendet. Gemeinsame schließbare Status-/Fehlermeldungen im Partnerbereich und der Selbstauskunft.

## Mitarbeiteranlage und Selbstauskunft (P-042)

Im Partnerbereich als Inhaber „Mitarbeiter“ öffnen, „Mitarbeiter anlegen“ wählen und ausschließlich fiktive lokale Prüfdaten verwenden. Name, E-Mail-Empfänger, Stammstation und Personalbogen angeben. Die Voranlage sendet noch nichts. In der Akte Empfänger prüfen/bestätigen und „Per E-Mail einladen“ verwenden; vorhandene Superadmin-Mailkonfiguration gilt auch hier, keine neue .env nötig. Bei Log-Mail steht der synthetische Link im lokalen Mailprotokoll; bei aktivem SMTP erfolgt die ausdrückliche Versandaktion an den eingegebenen Empfänger. Keine echten Empfänger für Tests verwenden. Die aktuelle Loopback-Adresse ist nur auf diesem Rechner erreichbar; ein tatsächlich externer Mitarbeiterzugang braucht später eine gesondert freigegebene HTTPS-Bereitstellung.

Einladung sieben Tage gültig; Link genau einmal in eine Browsersitzung aktivieren. Danach in derselben noch gültigen Sitzung öffnen, zwischenspeichern und einreichen. Bei Verlust/anderem Browser neu einladen; alte Links und Sitzungen werden ungültig, Entwurf bleibt für denselben Empfänger erhalten. Nach Einreichung Akte beim Inhaber neu laden, Arbeitgeberdaten ergänzen, gegebenenfalls zurückfragen, erneut einreichen lassen und abschließend bestätigen. Keine DATEV-Übertragung oder Benutzerrechtevergabe. Bestätigte Akte war in P-042 lesend; die spätere ausdrückliche Inhaberpflege P-047 erlaubt Änderungen mit Zugangssperre und neuer Prüffassung (siehe oben).

Migration `2026_10_19_180000_create_employee_onboarding` auf diesem Rechner bereits in Nova-Dev angewendet; nach Rechnerwechsel regulär mit eigener Nova-Laufzeit migrieren. Tests ausschließlich `merlin_nova_test`; Browserprüfung auf eigenem 8097 mit Log-Mail. Aktuelle offene Datenschutz-/Recovery-/Rollen-/Exportgrenzen vor echter Personalerfassung im Leitfaden P-042 beachten.

## Voraussetzungen auf beiden Rechnern

### Benzinpreisanbindung (P-034)

P-035: Nach Aktualisierung der Quelle „Öffnungszeiten und Koordinaten prüfen“ wählen, Quellwerte ansehen, gewünschte Datenarten auswählen und bestätigen. Nur leere Wochentage und ein vollständig leeres Koordinatenpaar werden ergänzt. Manuelle Angaben bleiben erhalten. Bei Neuanlage ist die Auswahl in Schritt 3 möglich. Mehrfachintervalle und Feiertagsangaben werden angezeigt, aber nicht automatisch übertragen.

„Ort ermitteln“ im Such- oder manuellen Adressformular fragt Nominatim erst nach Klick für eine vollständige deutsche PLZ ab. Bestehenden Ort bewusst leeren, wenn eine neue Ermittlung gewünscht ist. OSM-Attribution sichtbar, keine PLZ-Mittelpunkte als Stationskoordinaten. Keine Autocomplete-Abfragen beim Tippen; moderater Betrieb, gemeinsamer lokaler Rate-Limiter und Cache. Betreiber können `NOVA_POSTAL_ENDPOINT` auf einen kompatiblen HTTPS-Endpunkt setzen oder zum Abschalten leeren; bestehende `.env` wurde nicht verändert. Bei Skalierung öffentlichen Dienst und gemeinsames Rate-Limit neu bewerten. Siehe [Nominatim-Regeln](https://operations.osmfoundation.org/policies/nominatim/).

Im Stationsassistenten optional deutsche PLZ und Ort eingeben und „Stationen suchen“ wählen. Treffer prüfen, Anschrift bestätigen und erst am Ende Station anlegen. Bereits bestehende Stationen lassen sich unter „Stammdaten“ → „Benzinpreisanbindung einrichten“ bewusst zuordnen; deren manuelle Daten werden nicht überschrieben. Danach aktualisiert „Benzinpreise aktualisieren“ ausschließlich die getrennte Quellenansicht. Abrufe nur auf Knopfdruck, Detailcache fünf Minuten, Suchcache 15 Minuten. Nicht verfügbare/alte Preise werden entsprechend angezeigt. Keine Schlüssel oder `.env`-Änderung nötig.

Öffentliche HTML-Seiten von Benzinpreis-Aktuell.de, keine garantierte API. Auf ausdrücklichen Nutzerauftrag technisch aktiviert; es liegt keine gesonderte Anbieter-Nutzungsfreigabe vor und es wurde keine Anfrage versendet. Weitere Grenzen und tatsächliche Prüfungen im Leitfaden P-034. Nach Rechnerwechsel die additive Migration regulär mit der eigenen Nova-PHP-Laufzeit ausführen; keine Archivkonfiguration übernehmen.

### STRATO-Mailversand (P-022)

Die Superadminseite `http://127.0.0.1:8095/superadmin/email` erlaubt ausdrücklich aktivierten Registrierungsversand über `smtp.strato.de:465` mit direktem TLS oder seit P-024 Port 587 mit verpflichtendem STARTTLS. Mit bestehendem Superadmin anmelden, Benutzername/Absender prüfen, Passwort verdeckt eingeben, SMTP aktivieren und speichern. Danach kann ausdrücklich eine Testmail an die gespeicherte Absenderadresse ausgelöst werden. Partnerkonten haben keinen Zugriff. Ohne Aktivierung bleibt der lokale Log-/Arrayweg erhalten; `.env` muss dafür nicht verändert werden.

Das Passwort liegt verschlüsselt in der eigenen lokalen Datenbank, wird niemals zurück ins Formular geladen und beim Leerlassen erhalten. Nicht im Chat oder Repository hinterlegen. Datenbank-Backup und Anwendungsschlüssel schützen; eine Neuinstallation benötigt ihr eigenes Passwort. Registrierung bleibt bei Mailausfall unbestätigt, mit erneuter Versandmöglichkeit. Bestätigungslinks zeigen weiterhin auf localhost und müssen auf dem Nova-Rechner mit dem zugehörigen Konto geöffnet werden. SMTP-Annahme allein beweist keine Postfachzustellung. Historischer Ausgangsstand P-022: Zustellung damals noch offen. P-024/P-025 dokumentieren später erfolgreichen Versand und eine vom Nutzer gezeigte Bestätigungsmail; keine erneute Zustellprüfung in P-069.

P-023: Für die eigene Windows-PHP-Laufzeit kann `scripts/prepare-mail-ca.ps1` ein Mozilla-CA-Bündel von curl.se mit SHA-256-Prüfung unter `.local/mail/ca-bundle.pem` vorbereiten. Dieses gilt ausschließlich für den lokalen Nova-SMTP-Stream; keine globale PHP-/Windows-Konfiguration und kein Abschalten von Zertifikatsprüfungen. Beim Rechnerwechsel bei Bedarf separat vorbereiten. Am aktuellen Rechner aktiviert und geprüft, SMTP-TLS-Handshake endet dennoch mit Timeout; erfolgreiche Postfachzustellung weiterhin offen. Testmailseite unterscheidet nun TLS-, Timeout- und Anmeldefehler ohne Ausgabe von Server-/AUTH-Details.

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

## Partnerbereich und Stationsassistent (P-016)

Nach Anmeldung unter `/app` die eigene Firma auswählen. Die Partnerübersicht zeigt Testende (Europe/Berlin), zugesagtes Stationslimit und vorhandene Stationen. Inhaber und Verwaltung können während des aktiven Tests „Station anlegen“ wählen; andere Rollen sowie abgelaufene Tests bleiben lesend. Grunddaten, Anschrift und Zusammenfassung mit erfundenen Daten prüfen. Die optionale externe Suche ist bis zur Anbieter-/Bedingungsklärung nicht angebunden; manuelle Anlage benötigt keine API. Erst die ausdrückliche Bestätigung speichert.

Für dieses Paket nach Übernahme der Dateien ausschließlich `scripts/nova.cmd artisan migrate` aus der Projektwurzel ausführen; bestehende Konten/Konfiguration erhalten. Kein `migrate:fresh`, kein neues APP_KEY, keine Paketinstallation erforderlich. Prüfstand und verbleibende Abnahmen stehen im Leitfaden unter P-016. Dieses Paket wurde auf Nutzerwunsch noch nicht committet/gepusht.

## Stationsstammdaten im festgelegten Design (P-020)

In der Partnerübersicht führt „Stammdaten“ an einer Station zur neuen Detailmaske. Inhaber und Verwaltung sehen allgemeine Details, während Stationsbank-/Steuerdaten ausschließlich Inhabern vorbehalten bleiben. Mitarbeiter/Leser erhalten keine Detail-/Bild-/Finanzsicht. Nach Testablauf bleiben autorisierte Details lesbar; Bearbeiten/Uploads sind gesperrt.

Beim Übernehmen dieses Pakets `scripts/nova.cmd artisan migrate` aus der Projektwurzel ausführen. Die additive Migration ergänzt optionale Hausnummer und verschlüsselte Profile mit Partner-/Stations-FK; keine bestehenden Straßenzeilen/Konten/Testregeln zurücksetzen und keinen neuen APP_KEY erzeugen. Der bestehende Schlüssel wird nun auch für die Stationsprofile benötigt. Private JPG-/PNG-Bilder liegen unter `web/storage/app/private/station-media/`, niemals unter Public-Disk; vorhandene Bilder und Schlüssel nicht ungefragt übertragen/einchecken.

Bankkonten sind nur manuelle Stammdaten ohne Zahlung oder neues Mandat. Ausschließlich öffentliche IBAN-Testbeispiele verwenden. Es gibt keine Bankabfrage/IBAN-Berechnung und keine aktive Preis-/Geocoding-/Kartenanbindung. Neue Bankeingaben werden nach Fehlern nicht wiederbefüllt; gespeicherte Werte bleiben geschützt erhalten. Auswahlfelder mit fehlendem Quellkatalog zunächst als Text, Geräte-/Mitarbeiterbeziehungen weiterhin offen. Prüfungen, Grenzen und nächste Abnahme im Leitfaden P-020.
# Private Mitarbeiter-Nachweise (P-048)

Neue Dateien liegen verschlüsselt unter `web/storage/app/employee-documents`, nicht im öffentlichen Storage. Keine Symlinks oder Webserverfreigaben dafür anlegen. Dateiinhalt und Dateiname benötigen denselben bestehenden Anwendungsschlüssel; den Schlüssel weder ändern noch ausgeben. Die beiden additiven P-048-Migrationen ergänzen Auswahltexte und Dateimetadaten. Tests verwenden eine eigene Ablage unter `storage/framework/testing/employee-documents` und die vorhandene Testdatenbank.

Uploads: JPEG/PNG/PDF bis 2 MiB, keine Gesundheitsunterlagen. QR-Handyzugang derzeit nur gesperrter UI-Einstieg plus Planungsentwurf; erst mit freigegebener HTTPS-Adresse als Upload-only-Einmalübergabe implementieren/freischalten. Keine lokale Netzwerk-/Firewallfreigabe nötig oder vorgenommen. Physische Kamera, Malware-Scan und endgültige Aufbewahrungs-/Löschregeln sind offen. Lokal versionierte Bankreferenz gilt bis 06.12.2026; danach ist die automatische Bank-/BIC-Ergänzung gesperrt, bis die Quelle geprüft erneuert wird.
# Wettbewerb/Karten (P-053)

Additive Migration `2026_10_19_230000_create_station_competitors` ergänzt ausschließlich die getrennte Wettbewerbsablage. Leaflet 1.9.4 liegt mit Lizenz lokal unter `web/public/lib/leaflet-1.9.4`; kein npm-/CDN-Laufzeitbezug. `station_map.php` enthält die austauschbare Kacheladresse, Standard `https://tile.openstreetmap.org/{z}/{x}/{y}.png`. Keine lokale .env-Änderung. Erst „OpenStreetMap laden“ erzeugt Kachelanfragen; Origin-Referrer und Attribution erhalten, Browsercache nicht umgehen, keine Offline-/Vorabkacheln. Automatisierte Browserprüfungen ausschließlich gegen ignorierte lokale Testkacheln ausführen, keine OSM-Server durch Test-Pan/Zoom belasten.

Wettbewerbersuche verwendet die bestätigte bestehende Quelle Benzinpreis-Aktuell.de mit gewünschtem Stationsradius 2–25 km. Echtdatenkorrektur P-054: Die Quelle bestätigt nur Stufen 3/5/10/20 km, beliebige Werte fallen sonst still auf 5 km zurück. Nova verwendet passende unterstützte Stufen und prüft den Radius in der Quellüberschrift. 21–25 km sind nicht vollständig abgedeckt; der Hinweis muss sichtbar bleiben. Keine automatische Abrufserie je Treffer. Übernahme nach Einzelvorschau/Bestätigung; öffentliches HTML ist kein stabiler API-Vertrag. Die technische Aktivierung klärt Anbieter-Nutzungsrechte nicht. Echte Karten-/Quellprüfung für ARAL Fulda ist in P-054 dokumentiert; Produktionskartenanbieter bleibt gesondert offen.

## Automatische Wettbewerbsprüfung und geschlossene Stationspreise (P-060)

Nach Wettbewerbersuche Entfernungen nacheinander prüfen lassen; „Prüfung anhalten“ pausiert, „Entfernungsprüfung fortsetzen“ setzt bewusst fort. Bei Quellfehler oder Abrufgrenze bleibt die Serie stehen, keine automatische Wiederholung. Während der Prüfung keine Auswahl; danach oder nach Pause nur innerhalb des Radius liegende Treffer mit gültigen Koordinaten auswählen. Fehlende eigene Koordinaten zuerst unter Allgemein pflegen. Mehr als die Anbieterquote kann nicht in einer Serie abgefragt werden; Grenzen werden nicht hochgesetzt.

Karte lädt automatisch beim Öffnen des Kartenbereichs. Für geschlossene Stationen „Benzinpreise aktualisieren“ verwenden: falls die Quelle passende strukturierte Stationspreise liefert, erscheinen diese als zuletzt bekannt ohne behaupteten Preiszeitpunkt. Andere Datenquellen oder regionale Durchschnitte werden nicht eingesetzt. Bereits früher bekannte Preise bleiben bei leeren Abrufen erhalten. Neue Parserfassung verwendet eigenen Cache v5; keine .env-Änderung nötig.

## Alle Benzinpreise aktualisieren (P-064)

In den Stationsstammdaten „Alle Benzinpreise aktualisieren“ wählen. Erst die eigene Anbindung, danach die gespeicherten Quell-Wettbewerber werden automatisch nacheinander aktualisiert. Seite offen lassen; Fortschritt wird angezeigt. „Anhalten“ pausiert, „Aktualisierung fortsetzen“ setzt bewusst fort. Ohne JavaScript jeden Schritt per Button auslösen. Bei Quellfehler/Abrufgrenze stoppt der Lauf und bisherige Erfolge bleiben gespeichert. Quellen mit noch gültigem Detailcache verwenden denselben Stand statt erzwungenem Neuabruf. Der Lauf gilt fünfzehn Minuten; danach neu starten. Manuelle Preisangaben bleiben erhalten. Kein geplanter oder unbeaufsichtigter Aktualisierungsdienst.

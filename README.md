# Merlin Nova

Sauberer Projektneustart. Stand: 18.10.2026.

## Aktuell vorhanden

- [Zentraler Leitfaden](docs/MERLIN-NOVA-LEITFADEN.md) mit Anforderungen, Fortschritt und nächstem Schritt.
- [Architektur](docs/planning/ARCHITECTURE.md) und Baupläne für [Web-App](docs/planning/WEB-APP.md), [MDE](docs/planning/MDE.md) und [Druckagent](docs/planning/PRINT-AGENT.md).
- [Designentwürfe](docs/planning/MOCKUPS.md) mit versionierten Bildern und Einordnung.

- Technische Grundlage unter `web/`: Laravel 13.37.0 und Filament 5.10.2, exakte Paketstände in den Lockdateien.
- Neues Git für das bestätigte Repository; natives lokales MySQL 8.4.11 ohne Docker, eigener Port 3307 und eigene Datenverzeichnisse.
- [Einrichtung und Tests](docs/LOCAL-DEVELOPMENT.md), mit getrennten lokalen Zugangsdaten je Rechner.

Lokale Anmeldung, Partner-Mitgliedschaften mit Rollen und ein getrenntes Superadminpanel sind vorhanden. Es gibt keine voreingestellten Zugänge. [Einrichtung des lokalen Superadmins und Rechte-Matrix](docs/LOCAL-DEVELOPMENT.md) beachten. Fachliche Registrierung, Abos und Personalmodule fehlen noch. Phase 1 ist noch nicht abgeschlossen: unter anderem MFA, Designsystem, CI und Prüfung auf Rechner 2 stehen aus.

## Altstand

Der bisherige lokale Stand einschließlich Git-Historie, vorgemerkter Änderungen und lokaler Daten bleibt unter `C:\xampp\htdocs\merlin-nova_archiv` erhalten. Seine Inhalte sind keine automatisch freigegebenen Bestandteile des Neuaufbaus. Nur Planungsunterlagen und Mockups wurden kopiert. Das Livesystem bleibt unberührt.

Die alten lokalen Konfigurationsdateien enthalten gegebenenfalls Pfade des früheren Ordners. Den archivierten Server nicht einfach starten; ein Wiederanlauf des Altstands wäre ein eigener Prüfauftrag.

## Nächster Abschnitt

Als nächstes Fachpaket folgen Landingpage, Partnerregistrierung und zentral gesteuerte Test-/Aboverwaltung. Offene Sicherheits-/Betriebsarbeiten bleiben im Leitfaden sichtbar; keine Produktivfreigabe.

Der Projektwurzelordner ist für Apache gesperrt. Die spätere Web-Anwendung darf erst mit eigenem vorgesehenem öffentlichen Einstieg gestartet werden, nicht durch Freigabe des gesamten Repositorys.

Die lokale Statusseite läuft auf `http://127.0.0.1:8095`. Sie ist ausdrücklich noch keine Landingpage oder Anmeldemaske.

Anmeldung: `http://127.0.0.1:8095/login`; Plattform: `http://127.0.0.1:8095/superadmin/login`.

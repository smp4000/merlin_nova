# Merlin Nova Architektur und Entwicklungsablauf

Stand: 17.10.2026. Planungsstand, kein Installationsnachweis. Der [Leitfaden](../MERLIN-NOVA-LEITFADEN.md) führt Status und Entscheidungen.

## Architekturziel

Ein überschaubares Produkt aus drei Anwendungen und einem zentralen fachlichen Backend:

- Web-App mit Landingpage, Partnerbereich und getrenntem Plattformadminbereich.
- Native Android-MDE-App für die Arbeit in der Station.
- Windows-Druckagent für freigegebene lokale Drucker.

MDE und Druckagent sprechen ausschließlich die versionierte, authentifizierte Backend-API an, niemals direkt MySQL. Ein modularer Monolith ist der Vorschlag: keine zusätzlichen fachlichen Microservices, keine doppelte Geschäftslogik auf jedem Client und keine Kopie aller alten Module als Startpunkt.

## Stack

| Bestandteil | Vorgabe / Vorschlag | Festlegung beim Projektstart |
| --- | --- | --- |
| Web-Backend | Aktuelle stabile Laravel-Hauptversion; derzeit geprüfte Dokumentationslinie 13.x | Stabile Patchversion, Sicherheitsstatus und Paketkompatibilität erneut prüfen; Composer-Lockdatei einchecken |
| Backoffice-Technik | Aktuelle stabile Filament-Hauptversion; geprüfte Dokumentationslinie 5.x | Zusammen mit Laravel/PHP testen, kein blinder Major-Wechsel |
| PHP | Unterstützte kompatible Version, Projektbasis mindestens 8.4 | Exakte Laufzeit je Rechner dokumentieren; keine Annahme, dass XAMPPs PHP passt |
| Datenbank | MySQL mit InnoDB, unterstützte LTS-Linie als Vorschlag | Verfügbare stabile LTS, Hosting und lokale Umgebung prüfen; MariaDB nicht stillschweigend gleichsetzen |
| Individuelle Oberfläche | Blade/Livewire, gemeinsames eigenes Designsystem und Buildprozess | Designfreigabe vor breitem Maskenbau; Frameworkteile nur gezielt nutzen |
| MDE | Kotlin, native Android-Oberfläche; lokale Datenbank und Hintergrund-Synchronisierung | Android-/Gerätestand, Scanner-SDK und kompatible stabile Bibliotheken festlegen |
| Druckagent | C# mit unterstützter .NET-LTS und austauschbaren Druckadaptern | DYMO-SDK-/Windows-/Architektur-Kompatibilität am echten Gerät prüfen |
| Hintergrundaufgaben | Ein zentraler Scheduler und persistente Jobs; zunächst ohne unnötige Infrastruktur | Atomare Locks, Wiederanlauf und Monitoring gehören zur Abnahme |

„Aktuellste Version“ bedeutet stabile, unterstützte und untereinander geprüfte Versionen, nicht Entwicklungszweige oder automatische Major-Upgrades. Es wird jetzt nichts installiert. Die offiziellen Laravel- und Filament-Dokumentationen bestätigen die genannten Hauptlinien, nicht eine für alle künftigen Zeitpunkte gültige Patchnummer.

## Filament ohne Oberfläche von der Stange

Landingpage und Onboarding werden eigens entworfen. Der Partnerbereich bekommt eine eigene Informationsarchitektur, Navigation, Seitentypen, Typografie und Bedienlogik. Filament liefert geeignete technische Bausteine für sichere Aktionen, Formulare und Tabellen; Standardressourcen werden nicht ungeprüft als fertige Benutzeroberfläche veröffentlicht.

Gemeinsame Muster: Übersicht, Liste, Arbeitsmaske, Einrichtungsassistent, Planungskalender, Dokumentansicht. Keine globale Sammlung aus zusätzlichen CSS-Überschreibungen pro Maske. Anpassungen werden in dokumentierten Komponenten und Design-Tokens gebündelt; invasive Änderungen an Framework-Innenteilen vermeiden.

## Vorgeschlagene Projektstruktur

Ein gemeinsames Git-Repository mit `web/`, `mde/`, `print-agent/` und `docs/`. Im Backend klare fachliche Grenzen für Identity/Access, Partners, Stations, Subscriptions/Billing, Employees/Contracts, Scheduling, Inventory, Cash, Giveaways, Faults und Printing. Die genauen Verzeichnisnamen werden vor der Installation beschlossen; es werden keine leeren Module nur zur Dekoration erzeugt.

HTTP-/UI-Schichten validieren Eingaben und rufen Fachaktionen auf. Fachaktionen prüfen Rechte, Mandantenbezug, Zustandsübergänge und Schreibschutz selbst. Rechnungsstellung, Schichtwechsel und Druckaufträge werden nicht an beliebige Formular-Speicherereignisse gekoppelt.

## Daten und Mandantentrennung

Partnerbezogene Datensätze tragen eine Mandanten-ID; Stationsdaten zusätzlich ihre Station. Fremdschlüssel und zusammengesetzte Eindeutigkeiten verhindern ungültige Zuordnungen. Benutzer dürfen keine IDs aus einem anderen Mandanten einschleusen, auch nicht in Dokumentlinks, Exportfiltern, Suchfeldern oder Queue-Jobs.

Ein zentraler Superadminbereich ist keine normale Partnerstation und kein automatischer Freibrief zum Lesen von Personalakten. Supportzugriff ist ein eigener befristeter, zweckgebundener und protokollierter Vorgang. Besondere Bearbeitungsrechte müssen getrennt freigegeben werden; Standardvorschlag ist lesender Zugriff.

Geldbeträge werden nicht mit binären Fließkommazahlen gerechnet. Belege und abgeschlossene Vorgänge erhalten unveränderliche relevante Datenstände. Zeitpunkte werden konsistent gespeichert; Anzeige und fachliche Tagesgrenzen folgen der Stationszeitzone, zunächst Europe/Berlin. Sommerzeit und Nachtwechsel werden getestet.

## Zwei Entwicklungsrechner

Aktueller Stand vom 18.10.2026: Das vollständige Altprojekt liegt unverändert unter `C:\xampp\htdocs\merlin-nova_archiv`. Im neuen Ordner `C:\xampp\htdocs\merlin-nova` sind Planung, neues Git, Laravel 13.37.0, Filament 5.10.2 und natives MySQL 8.4.11 eingerichtet. Docker ist ausdrücklich ausgeschlossen. Fachliche Funktionen fehlen noch; aktueller Fortschritt im Leitfaden.

1. Den bisherigen Stand behalten und offene lokale Änderungen zunächst ohne Geheimnisse sichern. Das bestehende GitHub-Repository nicht löschen.
2. Bestätigter Projektordner bleibt `C:\xampp\htdocs\merlin-nova`. Den belegten Altordner vorher vollständig als `merlin-nova-archiv-2026-10-18` erhalten. Die Umbenennung ist derzeit durch eine Windows-Dateisperre blockiert; noch kein neuer Ordner. Das vom Nutzer angegebene Zielrepository `https://github.com/smp4000/merlin_nova.git` wurde am 18.10.2026 erfolgreich ohne Remote-Referenzen gelesen. Später nur im tatsächlich neuen Ordner Git neu einrichten und vor dem ersten Push den Remotezustand erneut prüfen; alte lokale `.git` nicht löschen oder zurücksetzen. Aktuell keine Initialisierung und kein Push erfolgt.
3. Beide Rechner nutzen dieselben Lockdateien, Migrationen, Demo-Seeder und Start-/Prüfanleitungen.
4. Jeder Rechner hat eigene `.env`, Datenbank, Schlüssel und Geräteanmeldungen. Diese werden nicht über Git synchronisiert.
5. Vor Arbeitsbeginn `git pull --ff-only`; vor Rechnerwechsel Änderungen prüfen, Leitfaden aktualisieren, Tests ausführen, committen und pushen.
6. Bei paralleler Arbeit getrennte fachliche Branches und bewusste Zusammenführung; keine Synchronisierung des Arbeitsordners über OneDrive als Ersatz für Git.

Native lokale MySQL-Instanz ohne Docker und ohne Windows-Dienstinstallation, mit eigenem Benutzer, Datenverzeichnis unter `.local/mysql/data` und ausschließlich `127.0.0.1:3307`. Vor Belegung auf Konflikte prüfen. Keine Verwendung vorhandener Live-/XAMPP-Datenbanken, kein Neustart gemeinsam genutzter Dienste. Einrichtung über `scripts/setup-local.ps1` auf beiden Rechnern.

Die alte Nova-Schutzlogik akzeptiert gezielt SQLite und darf nicht einfach abgeschaltet werden, um MySQL anzuschließen. Der Neuaufbau erhält einen eigenen lokal fail-closed arbeitenden Schutz mit getesteter Allowlist für Host, Port, Datenbank und Mailtransport. Ausgehende Integrationen nutzen lokal zunächst Fakes. Echte Preisabfragen nur nach gezielter Freigabe des betreffenden Dienstes; keine unbemerkte Freigabe aller Netzwerkziele.

Gerätetests nutzen eine eigene App-Kennung, eigene Agentenkennung und Testdruckstelle. Kein produktives MDE neu registrieren und keinen vorhandenen Produktivdruckagenten auf die Entwicklung umstellen. Ein physisches Testgerät wird anhand seiner Gerätekennung ausdrücklich ausgewählt.

## Sicherheit und Betriebsreife

- Serverseitige Rechte nach Fähigkeit und Station; Tests für erlaubte und verbotene Fälle.
- MFA für privilegierte Konten; sichere Wiederherstellung und Widerruf von Sitzungen/Geräten.
- Transportverschlüsselung, geschützter Dateispeicher, nachvollziehbare Schlüsselverwaltung und -rotation.
- Keine Personal-/Bank-/Passwortwerte in Logs, Telemetrie, Demo-Daten oder Git.
- Abhängigkeiten prüfen, Artefakte versionieren, Updates erst nach Tests ausrollen.
- Backups müssen mit Schlüsselmaterial und Wiederherstellungsverfahren getestet werden, nicht nur „vorhanden“ sein.
- Datenschutzunterlagen, Rollen der Beteiligten, AV-Verträge, Speicherorte und Löschkonzept vor Pilotstart prüfen lassen. Keine pauschale Zusage „absolut sicher“ oder „automatisch DSGVO-konform“.

## Gemeinsame API und Fehlerbehandlung

Versionierte API mit dokumentiertem Vertrag, eindeutigen Vorgangs-IDs, stabilen Fehlercodes und menschenlesbaren Meldungen. Schreibvorgänge erhalten Idempotenzschlüssel und eine kanonische Inhaltsprüfung. Gleicher Schlüssel und gleicher Inhalt liefern dasselbe Ergebnis; veränderter Inhalt unter demselben Schlüssel führt zu einem eindeutigen Konflikt, nicht zu stiller Überschreibung.

Änderungskonflikte werden per Versionsstand erkannt. Clients dürfen Daten nicht mit „letzter Upload gewinnt“ unbemerkt ersetzen. Correlation-IDs ermöglichen Fehlersuche ohne das Mitschreiben sensibler Inhalte. Geld-, Schicht- und Abovorgänge benötigen Datenbanktransaktionen und geeignete Eindeutigkeitsregeln; ein ausgegrauter Knopf ist kein Schutz gegen Parallelität.

## Qualitätsprüfung je Lieferabschnitt

Unit-Tests für Regeln; Featuretests für Daten und Rechte; Integrationstests gegen dieselbe MySQL-Linie wie die Zielumgebung; Browserprüfungen für Kernabläufe; echte Scanner-/Druckertests. SQLite allein reicht nicht als Nachweis für MySQL-Locks und Parallelität.

Desktopprüfungen mit typischen 27-Zoll-Auflösungen, beispielsweise 1920×1080 und 2560×1440, sowie schmalen Fenstern, Zoom, Tastatur und Hell-/Dunkelmodus. Konkrete Pixelgrößen sind Testfälle, keine Annahme, dass jeder 27-Zoll-Monitor dieselbe Auflösung besitzt.

Jede Abnahme dokumentiert Versionen, Testdaten, Ergebnis und Restlücken im Leitfaden. Keine Tests abschalten oder Erwartungswerte abschwächen, um eine rote Prüfung zu verbergen. Eine bewusst genehmigte Ausnahme muss mit Ursache und Genehmigung erkennbar bleiben.

## Quellen für technische Ausgangsentscheidungen

- [Laravel Release Notes 13.x](https://laravel.com/docs/13.x/releases)
- [Filament 5 Installation](https://filamentphp.com/docs/5.x/introduction/installation)
- [MySQL LTS und Innovation Releases](https://dev.mysql.com/doc/refman/8.4/en/mysql-releases.html)
- [DYMO SDK und technische Referenzen](https://www.dymo.com/online-support-sdk.html)

Die Paket-/Laufzeitkombination wird unmittelbar vor der tatsächlichen Installation erneut geprüft. Die Planung installiert keine Abhängigkeiten und aktiviert keine dieser Integrationen.

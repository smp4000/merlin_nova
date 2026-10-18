# Merlin Nova Projektregeln

- Dies ist der saubere Neuaufbau. Das vollständige lokale Altprojekt liegt unter `C:\xampp\htdocs\merlin-nova_archiv` und wird nicht verändert oder ungeprüft übernommen.
- Das Livesystem, produktive Datenbanken, Geräte und Druckagenten bleiben unangetastet.
- Vor jedem Arbeitspaket `docs/MERLIN-NOVA-LEITFADEN.md` lesen. Nach jedem Schritt Umsetzung, tatsächliche Prüfungen, Lücken, nächsten Schritt und gegebenenfalls Commit-/Push-Stand dort aktualisieren.
- Laravel und Filament liegen unter `web/`; natives MySQL ohne Docker unter `scripts/`. Anmeldung, Mandanten-/Rollenbasis und ein getrenntes Superadmin-Panel sind lokal implementiert. Fachliche Registrierung, Abos und Personalmodule sind noch offen. Tatsächliche Prüfungen stehen im Leitfaden.
- Docker ist auf Nutzerwunsch ausgeschlossen. Kein Containerbetrieb, kein Dockerstart und keine Docker-Reparatur für dieses Projekt.
- Beschlossene Richtung: aktuelle stabile kompatible Laravel-/Filament-Versionen, MySQL und individuelles ruhiges Design statt unverändertem Filament-Standard. Versionen vor Installation prüfen und festschreiben.
- Erstes Lieferpaket: Landingpage, Partnerregistrierung und Superadmin-gesteuerte Tarife/Testphase/Abos. Details und Abnahme in `docs/planning/WEB-APP.md`.
- Tarif-/Testverwaltung ist als lokale Grundlage unter `/superadmin/tarife` vorhanden. Fassungen nur über `BillingCatalog` ergänzen/veröffentlichen, niemals historische Preise/Testregeln überschreiben. Lokale Registrierung mit zugesagtem Regel-Snapshot und Teststart nach E-Mail-Bestätigung ist implementiert; Abrechnung und Produktivbetrieb bleiben offen.
- Teststart nach E-Mail-Bestätigung, SEPA-Erfassung bei Registrierung ohne Abbuchung im Test, Kostenpflicht nur nach ausdrücklichem Abschluss mit gültigem Mandat. Nach Testende ohne Abschluss nur lesend. Kündigung zum Ende des bezahlten Zeitraums.
- Codebezeichner Englisch; Oberflächentexte, Kommentare, Dokumentation und Commitbeschreibungen Deutsch. Keine KI-Zusätze in Commitnachrichten; keine fremde Autorenidentität erfinden.
- Models, Filament-Ressourcen und wichtige Codebereiche ausführlich auf Deutsch dokumentieren: fachlicher Zweck, Verantwortlichkeit, Beziehungen, Berechtigungen, Sicherheitsannahmen und nicht offensichtliche Entscheidungen erklären. Kommentare sollen auch nach einem Jahr verständlich machen, warum der Code existiert; nicht bloß einzelne Anweisungen nacherzählen. Bei Änderungen die zugehörigen Kommentare mitpflegen.
- Keine Geheimnisse, realen Personal-/Bankdaten, lokalen Datenbanken oder `.env` einchecken. Keine Zugangsdaten aus dem Archiv kopieren.
- Keine Frameworks, Pakete, Produktionskonfigurationen oder Deployments ohne passenden Auftrag installieren/ändern. Keine alten Schutzregeln abschalten, um die neue MySQL-Umgebung anzuschließen.
- Neue lokale Dienste brauchen eigene Datenverzeichnisse, Ports und Berechtigungen. Vorhandene XAMPP-/Live-Dienste nicht ungefragt umkonfigurieren oder neu starten.
- Mockups sind keine implementierten Funktionen; visuelle Zustimmung ersetzt keine fachlichen, Sicherheits-, Geräte- oder Druckertests.
- Vorgesehenes Zielrepository: `https://github.com/smp4000/merlin_nova.git`. Vor Erstinitialisierung/Push Remotezustand erneut prüfen, nicht aus dem Altarchiv pushen, keine fremde Historie überschreiben.

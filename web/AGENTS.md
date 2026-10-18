# Merlin Nova Web-App

- Die Regeln aus `../AGENTS.md` und der zentrale Leitfaden gelten zusätzlich.
- Lokale Einrichtung und Prüfungen: `../docs/LOCAL-DEVELOPMENT.md`.
- Kein Laravel Boost oder weiterer Installer ohne konkreten Auftrag. Keine heruntergeladenen Shellskripte ungeprüft ausführen.
- Laravel-/Filament-Basis ist freigegeben; fachliche Umsetzung in klar begrenzten Paketen.
- Automatisch erzeugte Framework-Konfiguration ist keine Freigabe für produktive Dienste, Konten oder Migrationen.
- Vor Datenbanktests lokale Schutzregeln beachten; ausschließlich die getrennte MySQL-Testdatenbank verwenden.
- Models, Ressourcen, Policies, Mandantenlogik und andere wichtige Codebereiche ausführlich auf Deutsch kommentieren. Zweck, Zusammenhänge, Sicherheitsgrenzen und das Warum erklären; Kommentare bei Änderungen aktuell halten. Codebezeichner bleiben Englisch.

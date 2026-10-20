# Sicherungspakete und Geheimnisprüfung

Stand: 20.10.2026, P-071. Basis `33f5c97`. Die vorbereiteten Pakete werden auf Nutzerauftrag als sechs lokale Sicherungscommits versioniert; S6 enthält dieses Dokument. Vor jedem Commit erneute Indexkontrolle, kein Push. Die nachfolgende Fundauswertung beschreibt P-070 und bleibt historischer Prüfbericht. Keine Installation, Anwendungstests, Builds, Migrationen oder Dienststarts.

## Paketfolge

| Paket | Vorgeschlagene Commitbeschreibung | Inhalt und Abhängigkeit |
| --- | --- | --- |
| S1 | Mailverwaltung und lokalen Start vorbereiten | SMTP-Einstellungen, Bestätigungsmail und lokale Hilfsskripte; Panel-/Routenregistrierung folgt in S5 |
| S2 | Stationsverwaltung und Preisvergleich lokal ergänzen | Assistent, Stammdaten, Quellen, Karte, Wettbewerb, Sammelpreise und zugehörige Tests/Migrationen; braucht S4 und S5 |
| S3 | Mitarbeiterakten und Selbstauskunft lokal ergänzen | Einladungen, Pflege, Papierkorb, Nachweise, Auswahl-/Bankhilfen, Tests/Migrationen; braucht S1, Stationsbasis, S4 und S5 |
| S4 | Öffentliche Referenzdaten und Leaflet mit Lizenz sichern | Zwei Referenzdateien sowie Leaflet-JS/CSS/BSD-Lizenz; getrennt für Herkunfts-/Lizenzkontrolle |
| S5 | Fachbereiche und gemeinsame Schutzregeln verbinden | Gemeinsame Routen, Provider, Middleware, Formulare, Layout, Meldungen, Übersetzungen und fachübergreifende Tests |
| S6 | Projektstatus und Sicherungsprüfung dokumentieren | Projektregeln, Leitfaden, Fachpläne, Status und dieses Paketverzeichnis |

Das sind zusammengehörige Sicherungscommits desselben lokalen Entwicklungsstands, keine einzeln abgenommenen Releases. Vor S5 fehlen bewusst Verbindungen zwischen den Modulen; Zwischencommits sind nicht als ausführbare oder grüne Zwischenstände ausgewiesen. Migrationen werden nur versioniert, nicht ausgeführt. Für unabhängig lauffähige Featurecommits müsste man gemeinsame Dateien und historische Änderungen gezielt inhaltlich aufteilen und anschließend gesondert prüfen; das ist nicht Teil dieser Vorbereitung. Kein blindes `git add .`: später nur die aufgeführten Dateien eines Pakets vormerken und den Index nochmals prüfen.

## Prüfung auf Geheimnisse und sensible Daten

Gesichtet wurde der vollständige Satz der 170 bei Beginn geänderten/neuen Dateien (25 versionierte Änderungen und 145 neue Dateien). Inhaltsmuster wurden über die vollständigen Dateien gesucht, einschließlich bestehender Inhalte geänderter Dateien. Zusätzliche Token-/Langliteral-Heuristik ohne große öffentliche Referenzdaten und Leaflet; diese Dateien separat strukturell eingeordnet. Keine ignorierten Konfigurationsdateien, privaten Uploads, Datenbanken oder Laufzeitprotokolle geöffnet. Kein externer Scanner installiert oder Inhalt an einen Prüfdienst übertragen.

- Keine Treffer für typische private Schlüssel, GitHub-/Cloud-/API-Token, hart belegte APP_KEY-/DB_PASSWORD-/MAIL_PASSWORD-Werte, Zugangsdaten in URLs oder JWT-/Bearer-Token. Die ergänzende Langliteral-Suche ergab außerhalb der Referenz-/Leaflet-Dateien keinen Kandidaten. Das ist eine Musterprüfung, keine Garantie gegen jede mögliche Verschleierung.
- 28 E-Mail-Muster, sämtlich auf `example.test`. Keine echte persönliche Empfängeradresse im Kandidatensatz gefunden.
- 17 Passwort-/Secret-Zuweisungstreffer: synthetische Testpasswörter/-fehlermeldungen sowie Feldübersetzungen und verschlüsselter Model-Cast. Keine als echter Zugang identifizierte Belegung.
- 7 Personalfeldtreffer: synthetische Testdaten/Fehlereingaben (u. a. Fiktive Selbstauskunft, Musterort), keine identifizierte reale Personalakte.
- IBAN-Suche zunächst 24 Treffer; erweiterte Suche einschließlich Kleinschreibung und Leerzeichen 28. Einordnung: Nullkonten, bekannte deutsche/britische Formatbeispiele und absichtlich ungültige Varianten; zwei Fehlalarme aus technischen DATEV-Feldnamen. Keine als reale Partner-/Mitarbeiterbankverbindung identifiziert. Öffentliche BLZ/BIC-Daten sind Institutsreferenzen, keine Kundenkonten.
- Im Kandidatensatz keine `.env`, privaten Schlüsseldateien, Datenbankdumps, SQLite-Dateien oder Logs. Git verfolgt auch keine Dateien der zusätzlich abgefragten Geheimnis-/Datenbankdateiendungen. Ignorewirkung für `.env`, `web/.env`, `.local`, `.runtime` und private Uploads mit `git check-ignore` bestätigt. Tatsächliche Inhalte dieser ignorierten Ablagen wurden nicht gelesen oder mitgesichert.
- Anbieterfixtures nennen ausdrücklich fiktive Musterstationen. Historische Dokumentation enthält öffentliche Stationsanschriften/Preise und lokale Datensatzkennungen; keine neue Akten-/Bankdatenkopie erzeugt. Die Altprojektanalyse beschreibt Felder und Abläufe, nicht ausgelesene Personalakten.

Ergebnis: kein bestätigter Geheimnis-/Echtdatenfund, der eine Bereinigung der Kandidaten erfordert. Der Git-Index war bei P-070 leer. P-071 verwendet ihn anschließend ausschließlich paketweise; nach jedem Commit wird er auf Leerstand kontrolliert. Nicht geprüft: vollständige frühere Git-Historie, ignorierte Laufzeitdaten, umfassendes Sicherheitsreview der Fachlogik oder neue Laufzeitfunktion. Bei späteren Änderungen vor dem Commit den konkreten Index erneut prüfen.

## Herkunft und verbleibende Veröffentlichungspunkte

Leaflet 1.9.4 enthält die BSD-2-Clause-Lizenz mit Urhebervermerken; Lizenz zusammen mit den drei Bibliotheksdateien erhalten. Die Bankdatei enthält Quelle, Gültigkeit und Quellhash; das Krankenkassenverzeichnis Quelle und Abrufdatum. Ihre konkreten Weiterverwendungsbedingungen wurden heute nicht extern verifiziert. S4 ist fachlich getrennt, damit diese Frage vor einer späteren Veröffentlichung gezielt geklärt werden kann. Keine Makrodateien oder historischen Originalpersonalbögen im Kandidatensatz. Anbieter-Nutzungsfreigabe für Preisabrufe bleibt unabhängig davon offen.

## Vollständige Dateizuordnung

Die folgenden Listen verwenden Pfade relativ zum Repository. Jede lokale Änderung ist genau einem Paket zugeordnet. Diese Datei gehört selbst zu S6. Die Zuordnung verschiebt keine Dateien und verändert keinen Anwendungscode. Die Pakete sind Sicherungscommits; sie ersetzen keine Funktionsprüfung.

### S1 (15 Dateien)

- `scripts/prepare-mail-ca.ps1`
- `scripts/start-nova.ps1`
- `web/app/Filament/Pages/MailSettings.php`
- `web/app/Http/Controllers/Auth/RegistrationController.php`
- `web/app/Http/Controllers/MailSettingsController.php`
- `web/app/Models/PlatformMailSetting.php`
- `web/app/Notifications/RegistrationVerification.php`
- `web/app/Support/RegistrationMail.php`
- `web/database/migrations/2026_10_18_200000_create_platform_mail_settings.php`
- `web/database/migrations/2026_10_18_210000_add_smtp_port_to_platform_mail_settings.php`
- `web/resources/views/filament/pages/mail-settings.blade.php`
- `web/resources/views/mail/registration-verification-text.blade.php`
- `web/resources/views/mail/registration-verification.blade.php`
- `web/tests/Feature/MailSettingsTest.php`
- `web/tests/Feature/RegistrationMailDesignTest.php`


### S2 (66 Dateien)

- `web/app/Http/Controllers/PostalPlaceController.php`
- `web/app/Http/Controllers/StationCompetitionController.php`
- `web/app/Http/Controllers/StationController.php`
- `web/app/Http/Controllers/StationDetailsController.php`
- `web/app/Http/Controllers/StationPriceRefreshController.php`
- `web/app/Http/Controllers/StationSourceController.php`
- `web/app/Http/Controllers/StationWizardController.php`
- `web/app/Models/Station.php`
- `web/app/Models/StationCompetitor.php`
- `web/app/Models/StationProfile.php`
- `web/app/Policies/StationPolicy.php`
- `web/app/Stations/Providers/BenzinpreisHtmlParser.php`
- `web/app/Stations/Providers/BenzinpreisProvider.php`
- `web/app/Stations/Providers/PostalPlaceProvider.php`
- `web/app/Stations/Providers/StationDataProvider.php`
- `web/app/Stations/StationCatalogs.php`
- `web/app/Stations/StationCodes.php`
- `web/app/Stations/StationCompetitionChoices.php`
- `web/app/Stations/StationFields.php`
- `web/app/Stations/StationMap.php`
- `web/app/Stations/StationMedia.php`
- `web/app/Stations/StationPriceSnapshot.php`
- `web/app/Stations/StationProfiles.php`
- `web/app/Stations/StationSource.php`
- `web/app/Stations/StationSourceImport.php`
- `web/config/station_map.php`
- `web/config/station_sources.php`
- `web/database/migrations/2026_10_18_180000_add_station_onboarding.php`
- `web/database/migrations/2026_10_18_190000_create_station_profiles.php`
- `web/database/migrations/2026_10_18_220000_add_station_short_code.php`
- `web/database/migrations/2026_10_18_230000_add_station_external_source.php`
- `web/database/migrations/2026_10_19_230000_create_station_competitors.php`
- `web/public/css/nova-station-map.css`
- `web/public/js/nova-competition-selection.js`
- `web/public/js/nova-postal-lookup.js`
- `web/public/js/nova-price-refresh.js`
- `web/public/js/nova-station-details.js`
- `web/public/js/nova-station-map.js`
- `web/resources/views/stations/catalog-options.blade.php`
- `web/resources/views/stations/competition-search.blade.php`
- `web/resources/views/stations/competitor.blade.php`
- `web/resources/views/stations/details.blade.php`
- `web/resources/views/stations/field.blade.php`
- `web/resources/views/stations/map.blade.php`
- `web/resources/views/stations/postal-lookup.blade.php`
- `web/resources/views/stations/price-refresh.blade.php`
- `web/resources/views/stations/source-configure.blade.php`
- `web/resources/views/stations/source-import-choices.blade.php`
- `web/resources/views/stations/source-import.blade.php`
- `web/resources/views/stations/source-observations.blade.php`
- `web/resources/views/stations/source-preview.blade.php`
- `web/resources/views/stations/wizard.blade.php`
- `web/tests/Feature/BenzinpreisTransportTest.php`
- `web/tests/Feature/PostalPlaceTest.php`
- `web/tests/Feature/StationCompetitionTest.php`
- `web/tests/Feature/StationDetailsTest.php`
- `web/tests/Feature/StationPriceRefreshTest.php`
- `web/tests/Feature/StationSourceImportTest.php`
- `web/tests/Feature/StationWizardTest.php`
- `web/tests/Fixtures/benzinpreis/details.html`
- `web/tests/Fixtures/benzinpreis/search.html`
- `web/tests/Unit/BenzinpreisParserTest.php`
- `web/tests/Unit/StationCodesTest.php`
- `web/tests/Unit/StationCompetitionChoicesTest.php`
- `web/tests/Unit/StationPriceSnapshotTest.php`
- `web/tests/Unit/StationSourceObservationsTest.php`


### S3 (49 Dateien)

- `web/app/Employees/BankDirectory.php`
- `web/app/Employees/EmployeeChoices.php`
- `web/app/Employees/EmployeeDocuments.php`
- `web/app/Employees/EmployeeFields.php`
- `web/app/Employees/EmployeeInvitationMail.php`
- `web/app/Employees/EmployeeManagement.php`
- `web/app/Employees/EmployeeOnboarding.php`
- `web/app/Http/Controllers/BankDirectoryController.php`
- `web/app/Http/Controllers/EmployeeChoiceController.php`
- `web/app/Http/Controllers/EmployeeController.php`
- `web/app/Http/Controllers/EmployeeDocumentController.php`
- `web/app/Http/Controllers/EmployeeManagementController.php`
- `web/app/Http/Controllers/EmployeeSelfServiceController.php`
- `web/app/Http/Middleware/ProtectEmployeePages.php`
- `web/app/Models/Employee.php`
- `web/app/Models/EmployeeChoice.php`
- `web/app/Models/EmployeeDocument.php`
- `web/app/Models/EmployeeInvitation.php`
- `web/app/Models/EmployeeReview.php`
- `web/app/Policies/EmployeePolicy.php`
- `web/app/Rules/Iban.php`
- `web/database/migrations/2026_10_19_180000_create_employee_onboarding.php`
- `web/database/migrations/2026_10_19_190000_add_invitation_delivery_error.php`
- `web/database/migrations/2026_10_19_200000_add_employee_trash.php`
- `web/database/migrations/2026_10_19_210000_create_employee_choices.php`
- `web/database/migrations/2026_10_19_220000_create_employee_documents.php`
- `web/public/css/nova-employee-inputs.css`
- `web/public/css/nova-employees.css`
- `web/public/js/nova-employee-inputs.js`
- `web/public/js/nova-employee-tabs.js`
- `web/public/js/nova-employees.js`
- `web/resources/views/employees/bank-tools.blade.php`
- `web/resources/views/employees/choices.blade.php`
- `web/resources/views/employees/create.blade.php`
- `web/resources/views/employees/documents.blade.php`
- `web/resources/views/employees/edit.blade.php`
- `web/resources/views/employees/error.blade.php`
- `web/resources/views/employees/errors.blade.php`
- `web/resources/views/employees/fields.blade.php`
- `web/resources/views/employees/index.blade.php`
- `web/resources/views/employees/input.blade.php`
- `web/resources/views/employees/public/form.blade.php`
- `web/resources/views/employees/public/invitation-text.blade.php`
- `web/resources/views/employees/public/layout.blade.php`
- `web/resources/views/employees/public/start.blade.php`
- `web/resources/views/employees/show.blade.php`
- `web/resources/views/employees/trash.blade.php`
- `web/tests/Feature/EmployeeOnboardingTest.php`
- `web/tests/Feature/EmployeeReferenceTest.php`


### S4 (5 Dateien)

- `web/public/lib/leaflet-1.9.4/leaflet.css`
- `web/public/lib/leaflet-1.9.4/leaflet.js`
- `web/public/lib/leaflet-1.9.4/LICENSE`
- `web/resources/reference/banks-de.json`
- `web/resources/reference/health-insurers.json`


### S5 (24 Dateien)

- `web/app/Http/Controllers/Auth/SessionController.php`
- `web/app/Http/Controllers/PartnerController.php`
- `web/app/Http/Middleware/ResolvePartner.php`
- `web/app/Providers/AppServiceProvider.php`
- `web/app/Providers/Filament/PlatformPanelProvider.php`
- `web/bootstrap/app.php`
- `web/lang/de/validation.php`
- `web/lang/en/validation.php`
- `web/public/css/nova-notifications.css`
- `web/public/css/nova-partner.css`
- `web/public/css/nova-registration.css`
- `web/public/js/nova-notifications.js`
- `web/resources/views/auth/login.blade.php`
- `web/resources/views/auth/register.blade.php`
- `web/resources/views/auth/registration-session.blade.php`
- `web/resources/views/auth/verify-email.blade.php`
- `web/resources/views/layouts/partner.blade.php`
- `web/resources/views/partner/overview.blade.php`
- `web/resources/views/shared/notifications.blade.php`
- `web/resources/views/workspace.blade.php`
- `web/routes/web.php`
- `web/tests/Feature/GermanValidationTest.php`
- `web/tests/Feature/PartnerRegistrationTest.php`
- `web/tests/Feature/SessionSecurityTest.php`


### S6 (12 Dateien)

- `AGENTS.md`
- `docs/LOCAL-DEVELOPMENT.md`
- `docs/MERLIN-NOVA-LEITFADEN.md`
- `docs/planning/EMPLOYEE-FIELD-MATRIX.md`
- `docs/planning/EMPLOYEE-INPUTS-UPLOADS.md`
- `docs/planning/EMPLOYEE-LEGACY-REVIEW.md`
- `docs/planning/EMPLOYEES.md`
- `docs/planning/STATION-COMPETITION-MAP.md`
- `docs/planning/STATIONS.md`
- `docs/planning/WEB-APP.md`
- `docs/planung/STATUS.md`
- `docs/planung/SICHERUNGSPAKETE.md` (dieses Dokument)


## Lokaler Sicherungsstand P-071

| Paket | Commit |
| --- | --- |
| S1 | `43ff083` |
| S2 | `9403800` |
| S3 | `80fea44` |
| S4 | `6606df7` |
| S5 | `777e78a` |
| S6 | Dieser Dokumentationscommit; Kennung nach Erstellung in `git log` |

Vor jedem Commit explizite Paketpfade und Git-Blobinhalte kontrolliert und die Indexinhalte erneut nach Geheimnis-/Adressmustern durchsucht. Keine neuen sensiblen Funde. Keine Installation, Tests oder Push. Die Zwischencommits bleiben zusammengehörige Sicherungspakete und sind nicht einzeln als lauffähig geprüft.
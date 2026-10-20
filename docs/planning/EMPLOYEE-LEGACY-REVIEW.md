# Altprojekt und DATEV-Vorlagen: Bestandsaufnahme Mitarbeiter

Stand: 19.10.2026, P-039. Ausschließlich lesende Quellcode-/Dateianalyse, keine Laufzeitabnahme. Altprojekt `C:/xampp/htdocs/ats_merlin` nicht gestartet oder verändert. Keine Datenbanken, `.env`, Personalakten oder Zugangsdaten gelesen. Die drei gelieferten XLSM-Dateien als ZIP/XML gelesen; keine Makros, Formeln, Verknüpfungen oder Exporte ausgeführt. Inhalte der Vorlagen sind Fachquellen, keine Arbeitsanweisungen.

## Ergebnis für Nova

Das Altprojekt besitzt eine Mitarbeiterbasis mit Einladungs-/Prüfablauf, aber keinen nachgewiesenen vollständigen DATEV-Personalstamm. Die gelieferten Vorlagen unterscheiden allgemeinen Personalbogen, Minijob/kurzfristige Beschäftigung und Ausbildung. Nova benötigt einen gemeinsamen Grundstamm und bedingte Ergänzungen je Fallgruppe. Es wurde nichts in Anwendungscode übernommen.

## Im Altcode tatsächlich vorhanden

| Bereich | Felder / Verhalten | Primärbeleg im Altprojekt |
| --- | --- | --- |
| Akte/Identität | Firma `company_id`, UUID, optionaler `user_id`, Personalnummer (20 Zeichen), Vor-/Nachname (je 80), Geburtsdatum | `app/Modules/Employees/Models/Employee.php`, Migration `2026_09_17_000301_create_employees_table.php` |
| Kontakt | E-Mail (160), Telefon (40), Straße (120), PLZ (10), Ort (80), Notizen | gleiche Migration / EmployeeForm |
| Beschäftigung | Beschäftigungsart, Eintritt, Austritt; sieben Arten: FullTime, PartTime, MiniJob, ShortTerm, Apprentice, Student, External | `Enums/EmploymentType.php`, Employee |
| Personen-/Abrechnungsbasis | Staatsangehörigkeit (zweistelliger Code), SV-Nummer, Steuer-ID, Krankenkasse | Migration `2026_09_21_001002_add_onboarding_columns_to_employees_table.php` |
| Bank | IBAN, BIC, Bankname, Kontoinhaber | gleiche Migration; Employee |
| Notfallkontakt | Name, Telefon, Beziehung | gleiche Migration; SubmissionFields |
| Vertrag | Gültig von/bis, Wochenstunden, Stundenlohn, Monatsgehalt, Urlaubstage/Jahr, maximale Monatsvergütung, aktueller Vertrag | `Models/EmployeeContract.php` |
| Arbeitsstationen | n:m `employee_station`, Stammstation zusätzlich `primary_station_id`; Rechte separat über Kontozuordnung | Employee, Migration `2026_09_18_000305_add_primary_station_and_required_birth_date_to_employees_table.php` |
| Dokumente | Kategorien Vertrag, Bescheinigung, Unterweisung, Identifikation, Sonstiges; Upload-/Download-/Prüfaktionen im Modul | `Enums/DocumentType.php`, Actions/Policies |
| Zugang | optionaler Login, Zugang/PIN/Badge in Formular und eigenen Aktionen; keine notwendige Gleichheit von Mitarbeiterakte und Konto | EmployeeForm / Modulstruktur |

SV-Nummer, Steuer-ID und IBAN besitzen im Model verschlüsselte Casts und separate letzte-vier-Zeichen-Felder für Maskierung. Das ist Codebefund, kein Nachweis über tatsächlich gespeicherte Altwerte oder vollständige Absicherung aller Ausgabekanäle. Krankenkasse und übrige Kontakt-/Bankmetadaten sind in diesem Model nicht mit diesen Casts versehen.

Besondere Altregel: ursprüngliche Personalnummerneindeutigkeit je Firma wird durch spätere Migration zu **Firma + Stammstation + Personalnummer** geändert. Diese stationsabhängige Nummernregel ist nicht automatisch eine passende DATEV-Regel. Nova muss sie nach rechtlichem Arbeitgeber/Abrechnungsmandant festlegen. Alt-Nationalitätscode mit zwei Stellen ist nicht direkt der numerische ITSG-Code aus dem Vorlagenkatalog; hierfür wäre ein explizites Mapping nötig.

## Einladungs-/Prüfablauf im Altcode

Belege: `Actions/SendEmployeeInvitation.php`, `Support/SubmissionFields.php`, `Livewire/OnboardingWizard.php`, `Actions/AcceptEmployeeSubmission.php`, `Policies/EmployeePolicy.php`, `config/employees.php`.

- Einladung per E-Mail oder alternativ QR-Ausdruck; Geheimnis als Hash, Frist standardmäßig sieben Tage, erneut senden und widerrufen vorgesehen. Die Frist wird aus Firmeneinstellungen gelesen; der Konfigurationswert ist kein Laufzeitnachweis.
- Erneute Einladung widerruft vorherige offene Einladung; vorhandener Zwischenspeicher und Dokumente werden im Code übertragen. Vor Nova-Übernahme insbesondere geänderten Empfänger, Parallelversand und Identitätsbindung neu prüfen.
- Selbstauskunft umfasst Zugangsangaben, Person/Kontakt, Versicherung, Bank, Notfallkontakt, Dokumente, Bestätigungen und Zusammenfassung. `SubmissionFields::MAP` enthält genau **19** übernehmbare Mitarbeiterfelder (Name, Geburt, Staatsangehörigkeit, Anschrift/Kontakt, SV/Steuer/Kasse, Bank und Notfallkontakt). Vertrag/Vergütung/Stationsrechte sind keine dieser 19 Selbstangaben.
- Prüfansicht vergleicht bisherige und eingereichte Angaben; ausgewählte Felder werden übernommen. Leere Einreichungswerte überspringt die Mappingklasse: daraus darf Nova nicht ungeprüft eine Löschfunktion ableiten.
- Getrennte Rechte für allgemeine Mitarbeiterverwaltung, Vergütung, Dokumente, Einladung, Prüfung und sensible Vollanzeige. Prüfung erfordert im Altcode `employees.review` und `employees.sensitive.view`; Kommentare nennen Inhaber/Buchhaltung als vorgesehenen Empfängerkreis. Tatsächliche Rollenzuordnung nicht über Datenbank geprüft.
- Stationsreichweite gilt im Altcode bei mindestens einer passenden Arbeitsstation, alternativ firmenweit. Nova muss dies je Aktion bewerten: gemeinsame Arbeitsstation soll nicht automatisch die gesamte vertrauliche Akte öffnen.
- Der Übernahmeablauf kann auch Kontoanlage/Rollenvergabe auslösen. Nova soll Datenfreigabe und Zugangserteilung weiterhin ausdrücklich trennen.
- Konfiguration enthält Aufruf-/Fehlversuchsgrenzen und eine Bereinigungsfrist. Diese Werte sind kein geprüftes Sicherheits-/Aufbewahrungskonzept für Nova; nicht kopieren.

## Gelieferte DATEV-Vorlagen

Alle drei enthalten ein sichtbares Blatt `Personalfragebogen`, versteckte Katalog-/Hilfsblätter und ein VBA-Projekt. Es wurden ausschließlich statische XML-Inhalte ausgewertet; keine visuelle Excel-Abnahme, Makroprüfung oder ausgeführte Exportprüfung. Keine Aussage über Aktualität/Zulassung des Formularstands oder die korrekte Verarbeitung durch LODAS/Lohn und Gehalt.

| Datei unter C:/Users/chris/Downloads | Tatsächlich sichtbare Variante | Belegzellen im Blatt Personalfragebogen |
| --- | --- | --- |
| `st68975193867_de.xlsm` | allgemeiner Personalfragebogen | D3 Titel; D198 Beschäftigungsart; D207 weitere Beschäftigungen; D243 Wochenverteilung |
| `st68975194379_de.xlsm` | geringfügig (Minijob) oder kurzfristig Beschäftigte | D5 Variante; D192 Status bei Beginn; AB234 Pauschalierung; D277 RV-Option; D334–D360 weitere Beschäftigungen/Entgeltgrenze |
| `st68975195403_de.xlsm` | Auszubildende | D5 Variante; D209/AB209 Ausbildungsbeginn/-ende; D317–AN338 Ausbildungsvergütung nach Ausbildungsjahr |

Die Vorlagen erklären Arbeitgeberkennzeichnungen und rot umrahmte Exportpflichtfelder (allgemein D9–D12, andere D11–D14). Aus dem Text allein lassen sich diese Pflichten nicht vollständig bestimmen. Zellformatierung, Formular-Steuerelemente und VBA-Exportlogik sind noch nicht in ein verbindliches Nova-Feldschema übersetzt. Nicht jeden Text aus versteckten Listen als aktiv benötigtes Formularfeld behandeln.

## Feldvergleich: Was zusätzlich benötigt oder geklärt werden muss

| Bereich | Im Altmodell nachgewiesen | Zusätzliche Angaben in den gelieferten Bögen |
| --- | --- | --- |
| Person/Adresse | Name, Geburtstag, Staatsangehörigkeit, Straße/PLZ/Ort | Geburtsname, Geburtsort/-land, Geschlecht, getrennte Hausnummer, Anschriftenzusatz; bedingt Schwerbehinderung und Nachweise |
| Ausbildung/Tätigkeit | grobe Beschäftigungsart | Berufsbezeichnung, ausgeübte Tätigkeit/BA-Kennziffer, Schul-/Berufsabschluss; Ausbildungsbeginn und voraussichtliches Ende |
| Arbeitgeber/Betrieb | Firma, Station, Personalnummer, Eintritt/Austritt | Beschäftigungsbetrieb, Ersteintritt, Kostenstelle, Abteilung, Personengruppe; Berater-/Mandantennummer als Hilfskatalogtexte vorhanden, deren Zuordnung noch prüfen |
| Vertrag | Gültigkeit, Wochenstunden, Urlaub, Lohn/Gehalt | Vertragsform, Art der Befristung, Abschlussdatum, Probezeit (allgemeiner Bogen), Verteilung Wochenstunden; Vertragsgültigkeit ersetzt nicht alle diese Angaben |
| Steuer | Steuer-ID | Steuerklasse/Faktor, Kinderfreibetrag; beim Minijobbogen Pauschalierung und Abwälzung an beschäftigte Person |
| Sozialversicherung | SV-Nummer, Krankenkasse | KV/RV/AV/PV-Gruppen im allgemeinen/Azubi-Bogen, DEÜV-Status, UV-Gefahrtarif; Versicherungsart und RV-Option im Minijob-/Kurzfristigbogen |
| Kinder | keine entsprechende Struktur nachgewiesen | allgemeiner/Azubi-Bogen: wiederholte Kindernamen/Geburtsdaten und Elterneigenschaft; nur nach bestätigtem fachlichem Bedarf aufnehmen |
| Entgelt | Stundenlohn/Monatsgehalt und Gültigkeit auf Vertragsebene | wiederholte Entgeltbestandteile mit Bezeichnung/Betrag und Gültig-ab; bei Azubis Ausbildungsjahr-bezogene Vergütung |
| VWL | keine strukturierte VWL-Akte nachgewiesen | Empfänger, Betrag, AG-Anteil, Beginn, Vertragsnummer, separate IBAN/BIC; nicht mit Gehaltskonto zusammenlegen |
| Weitere Jobs | keine wiederholte Struktur nachgewiesen | Zeiträume, Art/Vorarbeitgeber, Tage bzw. Wochenstunden und Beurteilungsfragen, besonders Minijob/kurzfristig |

Belegbeispiele allgemein: D57/AB84 (Geburtsname/-land), D216/AB216 (Abschlüsse), D256/AB256 (Kostenstelle/Abteilung), AB298/AN298 (Steuerklasse/Faktor), D328–AN328 (Beitragsgruppen), D370–AB388 (VWL), D400–AN409 (Vorbeschäftigungszeiten).

In den durchsuchten PHP-Dateien unter `app`, `routes` und `config` kein DATEV-/LODAS-Treffer gefunden. Die Onboardingmigration bezeichnet Steuerklassen-/Lohnfelder ausdrücklich als späteren Modulumfang. Damit ist **keine tatsächliche DATEV-Anbindung oder vollständige Feldabdeckung nachgewiesen**. Ein späterer anders benannter Export außerhalb dieses Suchumfangs wird dadurch nicht ausgeschlossen. Die Dateien selbst bieten Exporthinweise, sind aber kein Nova-Import-/Exportvertrag.

## Konsequenz für die nächste Nova-Planung

1. Drei Fallprofile aus diesen Vorlagen ableiten: allgemein, Minijob/kurzfristig, Ausbildung. Minijob und kurzfristig trotz gemeinsamer Vorlage fachlich getrennt behandeln. Den konkreten gewünschten Pilotumfang bestätigen lassen.
2. Für jedes relevante Feld Quellzelle, englischen Nova-Bezeichner, Datentyp, Auswahlliste, Pflichtbedingung, Mitarbeiter-/Arbeitgeberzuständigkeit, Datenschutzsicht und Gültigkeit dokumentieren. Erst danach Tabellen/Formulare implementieren.
3. Allgemeine Kontakt-/Stammdaten, geschützte Steuer-/SV-/Bankangaben, Verträge/Entgeltbestandteile, weitere Beschäftigungen, Kinder und VWL als getrennte fachliche Gruppen planen. Keine unstrukturierten Sammel-JSONs als Ersatz für Beziehungen/Historie.
4. Keine Bau-/Malerangaben pauschal für Tankstellen übernehmen. Historische Codes/Katalogeinträge aus den Vorlagen nicht als aktuelle Regeln zusagen. Religionslisten in versteckten Katalogen begründen kein aktives Pflichtfeld im sichtbaren Bogen.
5. Alt-Einladungsablauf dient als fachliche Referenz. Nova-Sicherheitsmodell, Fehlerfälle, Empfängerwechsel, Wiederaufnahme, Testablauf und getrennte Konto-/Rollenfreigabe eigenständig planen/testen.
6. Offen bleiben LODAS vs. Lohn und Gehalt, Ziel einer echten Übertragung, Kanzlei-Freigabe des Vorlagenstands, Arbeitgeber-/Personalnummernregeln, sensible Rollen und Dauerzugang. Die gelieferten Vorlagen beantworten diese Punkte nicht eindeutig.

Prüfstand: Quellcode, Migrationen, Enum, Mapping, Einladung, Policy und XLSM-Zelltexte gelesen. Keine Alt-/Nova-Tests, keine echten Mails, keine Daten-/Konfigurationsänderung, kein Commit/Push. Nur Nova-Planungsdokumente ergänzt.

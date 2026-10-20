# Mitarbeiter, Stationsrollen und Selbstauskunft

## Verbindliche Fortschreibung P-047 (19.10.2026)

Auf ausdrücklichen Nutzerauftrag Inhaberpflege nach Prüfung sowie Empfänger-/Profiländerung ergänzt. Frühere Festschreibung der Akte/Empfänger/Fallgruppe gilt dafür nicht mehr. Persönliche Stammdaten und Arbeitgeberangaben werden getrennt katalogvalidiert; aktuelle Fassung „Vom Inhaber gepflegt“, frühere Prüffassungen bleiben unverändert. Änderungen sperren alle alten Einladungszugänge, neue Einladung ausschließlich aus aktuellen Inhaberstammdaten, kein Übertragen privater Altentwürfe an neue Empfänger. Papierkorb mit Bestätigung und Wiederherstellung, keine endgültige Vernichtung. Bestehende Tenant-/Inhaber-/Trialgrenzen gelten für jede neue Aktion. Tatsächliche Umsetzung/Prüfung und offene Lösch-/Produktionsgrenzen im Leitfaden P-047.


Standabgleich: 20.10.2026, P-069. Der folgende P-038-Entwurf beschreibt den historischen Planungsstand. Die lokale Umsetzung P-042 bis P-049 umfasst inzwischen Akten, Einladung/Selbstauskunft, Prüfung, Inhaberpflege, Papierkorb, Auswahlhilfen und direkte private Nachweise. QR-Handyübergabe, weiterführende Personal-/Stationsrechte, Verträge und DATEV bleiben offen. Aktuelle Einordnung im [Status](../planung/STATUS.md); spätere Fortschreibungen haben Vorrang vor ursprünglichen Vorschlägen.

## Bestätigter Auftrag und Ausgangslage

Mitarbeiter nach dem konkreten DATEV-/Kanzlei-Personalfragebogen erfassen. Einladung per E-Mail, damit der Mitarbeiter seine eigenen Stammdaten ausfüllt. Ruhiges kompaktes Nova-Design, gut lesbar auf großen Monitoren und mobil. Kein Commit/Push, kein echter Einladungsversand im Planungspaket.

Vorhanden: persönliche `User`, aktive `PartnerMembership` mit Owner/Manager/Staff/Viewer, `PartnerContext`, `PartnerOwnedModel`, Stationspolicies und Testablauf-Schreibschutz. Zum damaligen Planungsbeginn noch keine fachlichen Mitarbeiterakten, Stationsrollen, Personaleinladungen oder DATEV-Schnittstelle; Akten und Einladungen sind inzwischen lokal umgesetzt, Stationsrollen und DATEV weiterhin offen. Die Firmenrolle Manager erteilt heute keine Personalaktenrechte; diese dürfen nicht nebenbei ergänzt werden. W07 in WEB-APP.md bleibt Grundlage.

## Ziel und Pilotumfang (Vorschlag)

Ein Partner bereitet eine Mitarbeiterakte mit Name, Einladungsempfänger, geplantem Eintritt und Station vor. Der Mitarbeiter füllt einen persönlichen Onlinefragebogen aus und reicht ihn zur Prüfung ein. Die berechtigte Personalverwaltung kann Rückfragen stellen und einen geprüften Stand freigeben. Stationszuordnung, Beschäftigung und Benutzerberechtigungen bleiben getrennt.

Erster Pilot: ein Partner mit zwei Stationen, rein fiktive Daten, reguläre Beschäftigung und Minijob als abzustimmende Fallgruppen. Keine automatische Lohnberechnung, Meldung, Vertragsunterzeichnung, DATEV-Übertragung, MDE-/Offline-Erfassung oder Schichtplanung. Keine Zertifizierung oder DATEV-Kompatibilität ohne konkrete Feld-/Schnittstellenprüfung zusagen.

## Ablauf und Zustände (Vorschlag)

1. Personalverwaltung legt Entwurf an; Personalnummer und Arbeitgeber/Gesellschaft getrennt von Station festlegen. Dublettenhinweise innerhalb desselben Partners; Name/E-Mail sind keine sichere Personenidentität und kein globaler eindeutiger Mitarbeiterschlüssel.
2. Einladung vor Versand mit Empfänger und Zweck prüfen. E-Mail enthält nur neutralen Einladungstext und einen zeitlich begrenzten Link, keine Steuer-, Bank-, Vertrags- oder sonstigen Aktenwerte. Erst explizite Versandaktion sendet.
3. Token nur gehasht speichern, an Partner/Akte/Empfänger/Zweck binden. Vorschlag: sieben Tage Gültigkeit. GET öffnet nur Startseite, verbraucht den Token nicht (Mail-Linkscanner). Annahme per POST mit CSRF und erneuter Prüfung; bestehendes Konto korrekt authentifizieren, neue Identität nicht anhand gleicher E-Mail automatisch mit fremden Personalakten verbinden. Nach Annahme kurzlebige sichere Sitzung für genau diese Selbstauskunft; konkreter Anmelde-/Recoveryweg noch zu entscheiden. Token aus der URL entfernen, keine Drittinhalte/Referrerweitergabe, keine Tokens in Zugriffslogs.
4. Formularentwurf serverseitig speichern; klare Pflicht-/Optionalfelder nach Fallgruppe, Fortschritt und fehlende Angaben zeigen. Keine vertraulichen Felder in Browser-LocalStorage, URLs oder Session-Fehlereingaben spiegeln. Wiederaufnahme erfordert eigene Authentifizierung.
5. Mitarbeiter reicht Daten ein. Eingereichte Fassung wird versioniert; Arbeitgeberprüfung, Rückgabe mit feldbezogener Rückfrage, erneute Einreichung und Freigabe sind eigene Aktionen. Einreichung ist keine Vertragsunterschrift oder Lohnabrechnungsfreigabe an DATEV.
6. Nach Freigabe Änderungen als neuer Änderungsentwurf, nicht still über bereits freigegebene Werte schreiben. Austritt, Zugangsperre und Löschung sind getrennt. Optionalen dauerhaften Appzugang erst gesondert zuordnen; Einladung selbst erteilt keine Stationsverwaltung.

Fragebogen: Entwurf → eingeladen → in Bearbeitung → eingereicht → Rückfrage/in Bearbeitung oder freigegeben. Einladung separat: offen → angenommen/abgelaufen/widerrufen. Erneut senden widerruft den vorherigen Link atomar. Versandfehler bleibt sichtbar/wiederholbar; Wiederholung erzeugt keine zweite Akte. Paralleländerungen mittels Version prüfen. Abgelaufene/widerrufene Links zeigen neutrale Hilfe ohne Akteninformationen.

## Feldgruppen und Zuständigkeit

Die nachstehenden Gruppen sind Empfehlungen, keine endgültige DATEV-Pflichtfeldliste. Ein versionsbezogenes Mapping muss Quelle, Feldcode, Typ/Länge, Pflichtbedingung, Pflegeverantwortung und Exportfähigkeit dokumentieren.

| Gruppe | Mitarbeiter liefert | Arbeitgeber/Personalverwaltung pflegt |
| --- | --- | --- |
| Person/Kontakt | Name, Anschrift, erforderliche Geburtsangaben und Kontaktweg | Aktenzuordnung, verifizierter Stand |
| Steuer/Versicherung | erforderliche Steuer-ID, SV-/Kassenangaben und Bankverbindung nach Kanzleibogen | abrechnungsrelevante Einordnung und Prüfung |
| Ausbildung/Beschäftigungen | erforderliche Ausbildung, weitere Beschäftigung/Vorbeschäftigung; bedingte Angaben | Tätigkeit, Eintritt/Austritt, Beschäftigungsart und Kostenstelle |
| Vertrag | erforderliche Rückfragen zum vereinbarten Stand | Gültigkeit, Befristung, Stunden, Urlaub, Vergütung und Vertragsverlauf |
| Stationen/Rechte | kein selbstständiges Erteilen von Rollen | Stammstation, weitere Stationen, zeitliche Gültigkeit und ausdrückliche Rechte |

Kinder-/Religions-/Gesundheits-/Nachweisdaten nicht pauschal sammeln. Nur bei konkret bestätigter Notwendigkeit und separat begrenzter Sicht aufnehmen. Keine automatische ELStAM-/Kassenabfrage. E-Mail-Nachrichten und Anmerkungsfelder sind kein Sammelort für vertrauliche Daten. Uploads im ersten Paket optional ausklammern; falls erforderlich private Ablage, Größen-/Typprüfung, Schadsoftwareprüfung, autorisierte Downloads und Löschung vorsehen.

## Rollen und Reichweiten (Empfehlung zur Entscheidung)

| Rolle | Reichweite | Personalrechte |
| --- | --- | --- |
| Inhaber | eigener Partner | Akten und sensible Personalangaben, Freigabe; Rechtevergabe separat prüfen |
| Personalverwaltung | expliziter partnerbezogener Auftrag | Einladung, Prüfung, Personalpflege; sensible Daten nur mit eigener Berechtigung |
| Stationsleitung | ausdrücklich zugeordnete Stationen | notwendige betriebliche Personalliste; keine Steuer-/SV-/Bank-/Vergütungsdaten |
| Schichtleitung | zugeordnete Stationen | zunächst kein Personalaktenzugriff; spätere operative Rechte eigenes Paket |
| Mitarbeiter | eigene Akte, ausdrücklich erlaubte Abschnitte | eigene Auskunft/Änderungsentwürfe; keine fremden Akten oder Arbeitgeberfelder |
| Leser | eng begrenzte betriebliche Sicht | keine Personalakte allein durch Firmenrolle |
| Superadmin | Plattformverwaltung | kein automatischer Personalaktenzugriff; Supportzugriff gesondert befristet/protokolliert |

Vorgeschlagene getrennte Permissions: `employees.viewOperational`, `employees.manage`, `employees.viewSensitive`, `employees.editSensitive`, `employeeInvitations.send`, `employeeSubmissions.review`, `employeeAssignments.manage`, `stationPermissions.grant`, `payroll.export`. Alle mit Partner-/Stations-/Eigenaktenprüfung, nicht allein über Rollenname. Berechtigungen dürfen nicht über eigene Reichweite delegiert werden. Entzug wirkt bei jedem Serverrequest, nicht erst nach Neuanmeldung. Eine Arbeitsstationszuordnung verleiht keine Verwaltungsrechte.

## Datenmodell und Sicherheitsgrenzen (Entwurf)

`Employee` (Akte), `EmployeeSensitiveProfile` (geschützte Angaben), `EmploymentContract` (zeitlich gültige Vereinbarung), `EmployeeStationAssignment` (Arbeitszuordnung), `StationMembership` (Kontorechte), `EmployeeInvitation`, `EmployeeSubmission` und `EmployeeReviewEvent`. Benutzerkonto optional; mehrere Verträge/Stationen möglich. Eindeutige Personalnummer im bestätigten Arbeitgeber-/Abrechnungsmandantenbereich, nicht ungeprüft global. Gesellschaft, DATEV-Abrechnungsmandant und Nova-Partner nicht automatisch gleichsetzen. Doppelbeschäftigung bei mehreren Arbeitgebern braucht separate fachliche Zuordnung.

Nova nutzt bereits `partner_id` als Mandantenkennung: diese unveränderlich und zentral gescopt weiterführen, kein zweiter konkurrierender `tenant_id`-Kontext. Zusammengesetzte Fremdschlüssel/Eindeutigkeiten für Akte, Station, Benutzerbezug; IDs nie ungeprüft aus Formularen übernehmen. Fachaktionen autorisieren unter Transaktion erneut, inklusive Status/Version/Testablauf. Geheime Profilfelder nur gezielt laden, verschlüsseln und maskieren; keine generische JSON-/Such-/Exportfreigabe. Schlüssel-/Backupkonzept vor Produktivbetrieb prüfen.

Einladungsversand erst nach Datenbankcommit, mit eindeutiger Versandkennung und begrenzten Wiederholungen. Queue-Kontext enthält nur notwendige Referenzen; Roh-Token nur kurzfristig für Versand, nicht in Job-/Fehlerlogs speichern. Mailfehler und Widerruf dürfen keine spätere gültige Alt-Einladung ermöglichen. Anwendung bleibt ohne Queue-/SMTP-Verfügbarkeit manuell bedienbar. Keine echten Nachrichten im lokalen automatisierten Test.

Audit: Akteur, Partner, Aktion, betroffene interne Referenz, Zeit, Fassung und Ergebnis; keine Rohwerte sensibler Felder, Tokens oder kompletten Fragebogenschnappschüsse im allgemeinen Audit. Freigegebene Versionen geschützt und nachvollziehbar, aber nicht unbegrenzt aufbewahren. Lösch-/Aufbewahrungsmatrix nach Datenart/Zweck noch mit Verantwortlichem/Kanzlei prüfen; keine pauschalen Fristen erfinden. Datenschutzinformation vor Eingabe, Rechtsgrundlagen/Erforderlichkeit und DSFA-Prüfbedarf vor Pilot klären; keine pauschale Beschäftigteneinwilligung als Voraussetzung planen.

Testablauf: nach Testende keine neuen Einladungen, Einreichungen, Änderungen oder Freigaben; lesende Sicht nur innerhalb vorhandener Rechte. Im Formular abgelaufener Test liefert verständliche Meldung ohne Teiländerung. Recovery, Widerruf, Sicherheitsmaßnahmen und Betroffenenrechte als separate administrative Prozesse erhalten; keine Umgehung des Schreibschutzes durch offenen Link.

## Oberfläche und Benachrichtigungen

Partnernavigation Mitarbeiter: kompakte Liste mit Name, Stammstation und Bearbeitungsstatus. Filter für Station/Status, Suchergebnisse ohne sensible Angaben. Akte mit den Haupttabs aus W07; keine zweite Tabebene. Rollen als eigener verständlicher Bereich, effektive Rechte anzeigen. Mitarbeiterformular als kurze Schritte mit Zwischenspeichern, mobil mindestens 44-Pixel-Bedienflächen und 16-Pixel-Eingabeschrift. Arbeitgeberfelder dort nicht änderbar.

Einladungs-, Rückfrage- und Eingangsbestätigungsmails im Nova-Design; keine Aktenwerte. Erinnerungen nur bewusst beauftragt, Rate-Limit gegen Mailmissbrauch. Keine echte DATEV-Übermittlung, Payroll-Datei oder Steuerberatermail im ersten Paket. Kein Offlinezugriff auf Personalakten und kein automatisches Speichern sensibler Daten auf MDE/BYOD. Netzverlust zeigt Speicherausfall, keine falsche Erfolgsmeldung.

## Prüfplan, Einführung und Abnahmekriterien

- Positiv: Einladung → Annahme → Zwischenspeichern → Wiederaufnahme → Einreichen → Rückfrage → Freigabe; Mehrstationsmitarbeiter und Mitarbeiter ohne Appkonto; bestehendes Konto ohne Doppelanlage.
- Negativ: fremder Partner/Station/Mitarbeiter, Rollenmanipulation, Arbeitgeberfelder im Selfservice, unberechtigter Sensitivzugriff, Superadmin ohne Supportfreigabe, Rechteentzug während Sitzung, zeitgleich bearbeitete Fassung.
- Einladung: abgelaufen/widerrufen, zweimal angenommen, Linkscanner-GET, Neusendung/alte Links, E-Mail-Wechsel, SMTP-Fehler, Doppelklick, Rate-Limit, keine Offenlegung existierender Konten und keine protokollierten Tokens.
- Fachlich: Pflichtfelder je Beschäftigungsart nach bestätigtem Bogen, Personalnummernkollision, führende Nullen, ungültige Steuer-/Bankeingaben ohne Rohwert-Fehlerspiegelung, Vertragshistorie, Austritt ohne Datenverlust, stationenübergreifende Arbeitszuordnung ohne Rechteausweitung.
- Testablauf vor und während Bearbeitung: atomare Ablehnung aller fachlichen Schreibwege. Netzverlust/Reload, CSRF und Sessionwechsel prüfen. Desktop Full HD/QHD, Mobil und Tastatur inklusive Fehlerfokus testen.
- Akzeptanz: Kein fremder Datensatz erreichbar, Mitarbeiter bearbeitet ausschließlich eigene erlaubte Felder, kein Versand ohne bewusste Aktion, kein Rechtegewinn durch Arbeitszuordnung, Freigabe nachvollziehbar und parallele Änderungen erkannt. DATEV-Mapping erst nach Abnahme des konkreten Bogens vollständig nennen.

Neue Tabellen additiv, keine automatische Datenübernahme aus Archiv. Tests ausschließlich auf `merlin_nova_test` mit Fakes; Pilot mit synthetischen Datensätzen und Rücknahmeplan. Monitoring nur technische Fehler-/Versandstatus ohne Personalwerte; Supportansicht maskiert. Produktivfreigabe erfordert geklärte hohe Risiken (Identität/Wiederaufnahme, sensible Rechte, Aufbewahrung, Arbeitgeber-/DATEV-Zuordnung). Keine Installation oder Migration in diesem Planungsschritt.

## Offene Entscheidungen vor Umsetzung

1. DATEV LODAS oder Lohn und Gehalt? Welcher aktuelle Personalfragebogen der Kanzlei gilt? Nur Felder nach diesem Bogen oder später tatsächliche Übergabe an DATEV?
2. Fallgruppen für Paket 1: regulär Beschäftigte, Minijob, kurzfristig, Azubi, Werkstudent? Pflichtfelder abhängig davon abstimmen.
3. Wer prüft vertrauliche Angaben: ausschließlich Inhaber oder ausdrücklich benannte Personalverwaltung? Stationsleitung bekommt standardmäßig keine sensiblen Angaben.
4. Einmalige Selbstauskunft oder dauerhafter Mitarbeiterzugang mit Wiederaufnahme? Empfohlene sichere Anmeldung, Einladungsdauer und Recovery gemeinsam festlegen.
5. Ein Partner gleich ein rechtlicher Arbeitgeber oder mehrere Gesellschaften/Abrechnungsmandanten? Personalnummernregeln und erlaubte Parallelverträge klären.
6. Dokumentuploads und Signaturen im ersten Paket erforderlich? Datenschutz-/Löschmatrix und Umgang mit Minderjährigen festlegen.

## Geprüfte DATEV-Primärquellen

Am 19.10.2026 recherchiert: DATEV beschreibt einen Online-Personalfragebogen zum Ausfüllen durch neue Mitarbeiter und getrennte Freigabeabläufe. Daraus folgt weder eine freigegebene Nova-Schnittstelle noch eine endgültige Feldliste.

- https://www.datev.de/web/de/unternehmen/loesungen/lohn-und-personal/datev-personal-kampagnenseite
- https://www.datev.de/web/de/steuerberatung/loesungen/komplett-digital-im-lohn/daten-und-dokumente-von-mandanten-erhalten/digital-zusammenarbeiten-mit-datev-software/lohn-und-personaldaten-aus-datev-software-erhalten

Tatsächlich geprüft in diesem Paket: vorhandene Models, Rollenenum, Stationspolicy, Mandantenscope und W07 gelesen; offizielle DATEV-Beschreibungen recherchiert. Keine neue Funktion, kein Browserablauf und keine Tests ausgeführt; historische 220 Tests belegen dieses geplante Modul nicht.

## Ergänzende Bestandsaufnahme P-039

Die [lesende Auswertung des Altprojekts und der drei DATEV-Vorlagen](EMPLOYEE-LEGACY-REVIEW.md) ergänzt diesen Entwurf. Nachgewiesen sind eine kleinere Alt-Mitarbeiterbasis und 19 übernehmbare Selbstangaben, keine vollständige DATEV-Anbindung. Die gelieferten Bögen decken allgemeinen Personalstamm, Minijob/kurzfristige Beschäftigung und Ausbildung ab. Keine automatische Übernahme der Altregeln oder Implementierungsfreigabe; Feldmatrix und Zuständigkeiten folgen nach Abstimmung.

## Feldmatrix P-040

Die [Feldmatrix der drei DATEV-Vorlagen](EMPLOYEE-FIELD-MATRIX.md) ordnet Quellzellen, Mitarbeiter-/Arbeitgeberzuständigkeit, bedingte Nova-Pflichten und Sichtrechte zu. Nova-Pflichten/Zuständigkeiten sind Planungsvorschläge. P-041 verifiziert in Abschnitt 11 der Matrix die gespeicherten neun roten Exportpflichtfelder je Vorlage und sämtliche schwarzen Arbeitgeberbalken; zusätzliche dynamische Exportprüfungen bleiben offen. Keine Programmierung freigegeben.

## Implementierter erster Umfang P-042

Der Nutzer hat am 19.10.2026 Mitarbeiteranlage, E-Mail-Einladung, Selbstauskunft und Arbeitgeberprüfung zur Umsetzung beauftragt. Der dokumentierte Entwurf bleibt für spätere Pakete relevant; tatsächlicher Umfang und Tests im Leitfaden P-042.

Implementiert: Inhaber-only, ein rechtlicher Arbeitgeber je Partner, eine Stammstation, vier Fallprofile aus drei Bögen. Keine Konto-/Stationsrechtevergabe. Verschlüsselte katalogvalidierte Personen-/Arbeitgeberfassungen, private Entwürfe, getrennte Einreichung, Rückfrage und ausdrückliche Freigabe mit geschütztem Snapshot. Persönlicher Link sieben Tage gültig, einmaliger Tausch in Browsersitzung; Neusendung widerruft Vorgänger und erhält Entwurf für denselben unveränderlichen Empfänger. Keine Übernahme des Altprojekts.

Die erste Speicherung ist fassungsorientiert: wiederholbare Einträge werden innerhalb der verschlüsselten, streng strukturierten Fassung gespeichert, nicht als frei erweiterbares unvalidiertes Profil. Das ist noch kein normalisiertes Beschäftigungs-/Vertrags-/Lohnmodul. Bei späterem Einsatzplanungs-/Vertragsmodul Beziehungen und zeitliche Fachhistorie gezielt ergänzen, keine stillen Auswertungen aus unbestätigten Entwürfen.

Noch ausgenommen: dauerhafter Selfservice, Rollenverwaltung, mehrere Arbeitgeber/Arbeitsstationen, Änderungen freigegebener Akten, Upload/Signatur, aktuelle amtliche Schlüssel-/Grenzprüfung, DATEV-Export und produktive Datenschutz-/Aufbewahrungsfreigabe. Rot-/AG-Markierungen beeinflussen Zuständigkeiten, nicht automatische gesetzliche Pflicht oder Rollen. Unklare Kanzleifelder bleiben offen. Fachliche Datenfreigabe erteilt keine Benutzerrechte.

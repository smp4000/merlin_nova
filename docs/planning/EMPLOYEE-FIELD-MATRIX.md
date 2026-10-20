# Feldmatrix Mitarbeiter nach drei DATEV-Vorlagen

Stand: 19.10.2026, P-041 (ergänzt P-040). Planungsentwurf, keine Programmierung. Ergänzt [EMPLOYEES.md](EMPLOYEES.md) und [Bestandsaufnahme](EMPLOYEE-LEGACY-REVIEW.md).

## Quellen, Geltung und Leseschlüssel

Quellen sind ausschließlich die vom Nutzer gelieferten Dateien unter `C:/Users/chris/Downloads`, jeweils Blatt `Personalfragebogen`:

- **A**: `st68975193867_de.xlsm`, allgemeiner Personalfragebogen.
- **M**: `st68975194379_de.xlsm`, Minijob oder kurzfristige Beschäftigung. Beide Beschäftigungsarten getrennt beurteilen.
- **Z**: `st68975195403_de.xlsm`, Auszubildende.

Quellenzellen nennen **Beschriftungen**, nicht verbindliche Import-/Exportadressen. A/M/Z vor einem Zellbezug bezeichnet die Vorlage. Wo nur A oder M steht, ist kein zusätzliches Feld in den anderen Varianten unterstellt. Wiederholungszeilen werden zusammengefasst; Nova soll daraus wiederholbare Datensätze machen, nicht die feste Zahl gedruckter Zeilen erzwingen.

**Wichtig:** Zuständigkeit, Sichtklassen und mit N/B/O/K markierte Nova-Pflichten bleiben Planungsvorschläge. Die **gespeicherten roten Zellrahmen und schwarzen Arbeitgeberbalken sind unter Abschnitt 11 verifiziert**; sie ersetzen den bisherigen pauschalen X-Status. Rot bedeutet laut Vorlagenlegende Pflicht zum Erzeugen einer Exportdatei, nicht automatisch gesetzliche Pflicht oder Pflicht bei Nova-Einladung. Arbeitgeberbalken bezeichnet die Vorlagenzuständigkeit, nicht ein Sichtrecht. Keine Makros/ActiveX ausgeführt und keine Exportdatei erzeugt; zusätzliche dynamische Exportvalidierungen bleiben offen. Kein aktueller steuer-/sozialrechtlicher Schwellenwert wird hier festgelegt.

### Wer füllt aus?

- **MA**: Mitarbeiter liefert eigene Angaben; Personalverwaltung prüft. Keine stille Überschreibung freigegebener Daten.
- **AG**: Arbeitgeber beziehungsweise ausdrücklich berechtigte Personalverwaltung trägt ein; Mitarbeiter sieht eigene vereinbarte Angaben, soweit Sichtklasse dies vorsieht.
- **MA → AG**: Mitarbeiter meldet Tatsachen/legt Nachweise vor; Arbeitgeber/Kanzlei entscheidet Einordnung und bestätigt den abrechnungsrelevanten Wert.

### Wann Pflicht? (Nova-Vorschlag)

- **N**: vor jeweiligem Abschluss erforderlich, nicht schon zum Entwurf. Einladungsanforderungen separat unten.
- **B: Bedingung**: nur wenn der genannte Sachverhalt zutrifft. Leere optionale Zeilen nicht als fehlend zählen.
- **O**: optional, bei konkretem Bedarf erfassen.
- **K**: Kanzlei/Fachverantwortlicher muss Bedarf und Pflichtbedingung vor Umsetzung bestätigen. Bis dahin keine pauschale Blockade der Mitarbeiter-Einreichung und keine behauptete Abrechnungsreife.
- **—**: im Nova-Tankstellenpilot nicht vorgesehen, bei anderem Betrieb neu bewerten.

Ein nicht bekannter Wert bleibt „noch zu klären“; nicht mit 0, Nein oder erfundenem Datum füllen. Wo K/B offen ist, kann die Personalprüfung offen bleiben, während ein Mitarbeiter seine bekannten Angaben bereits einreicht. „Nicht zutreffend“ und „unbekannt“ getrennt speichern. Fehlende Arbeitgeberangaben dürfen die eigene Mitarbeitereinreichung nicht blockieren.

### Sichtklassen (Nova-Vorschlag)

| Kürzel | Mitarbeiter | Personalverwaltung / Inhaber | Stationsleitung |
| --- | --- | --- | --- |
| **P – persönlich** | nur eigene Angaben und eigene Änderungsentwürfe | nur mit Recht zur Personalaktenansicht im eigenen Partner | kein Zugriff auf persönliche Felder |
| **S – vertrauliche Abrechnung** | eigene mitgeteilte/übernommene Werte; maskiert, Vollanzeige nach erneuter Authentifizierung | eigener Partner und zusätzlich separates Sensitivrecht; Lesen/Ändern getrennt | kein Zugriff |
| **H – besonders geschützte Nachweise** | eigene Angaben/Nachweise | nur konkret benötigte, gesondert berechtigte Personalstelle | keine Rohangaben/Nachweise; erforderliche betriebliche Maßnahmen gesondert planen |
| **V – eigener Vertrag** | eigene bestätigte Vertragsangaben; kein Arbeitgeber-Editieren | Vertragsrecht erforderlich; Entgelt zusätzlich Sensitivrecht | keine Vertragsakte; ausdrücklich erlaubte Planungswerte als minimierte Ableitung |
| **E – Arbeitgeber-/Abrechnungseinordnung** | in eigener Selbstauskunft nicht editierbar, keine pauschale interne Vollansicht; gegebenenfalls verständliche eigene Ergebnisansicht | nur berechtigte Personal-/Abrechnungsstelle | kein Zugriff |
| **B – betriebliche Zuordnung** | eigene freigegebene Zuordnung | zuständiger Arbeitgeber mit Recht | ausschließlich eigene Station und notwendige betriebliche Angaben |

Kein Zugriff auf andere Mitarbeiter durch Selfservice. Allgemeine Firmenverwaltung/Leser erbt kein Personalrecht. Superadmin bekommt keinen automatischen Aktenzugriff. Steuerberater erhält nur ausdrücklich freigegebene Daten eines bestätigten Abrechnungsmandanten über einen später spezifizierten Übergabeweg. Rechte für Exporte/Downloads separat; keine Rohwerte in Listen, Mails oder allgemeinen Logs. Mitarbeiter-Auskunfts-/Korrekturprozesse bleiben von der Standard-UI-Sicht getrennt.

## 1. Arbeitgeber, Person und Kontakt

| Angabe / vorgeschlagener Schlüssel | Quellen A / M / Z | Eingabe | Wann erforderlich? | Sicht |
| --- | --- | --- | --- | --- |
| Rechtlicher Arbeitgeber `employer_id` | A D18; M/Z D20 | AG | N vor Einladung; Auswahl aus erlaubten Arbeitgebern, keine Freitext-Mandantenauswahl | B |
| Personalnummer `personnel_number` | A AN36; M/Z AN38 | AG | N vor Personal-/Abrechnungsfreigabe; vorläufige Akte darf ohne Nummer starten. Eindeutigkeit je bestätigtem Abrechnungsbereich klären | E |
| Name der beschäftigten Person am Kopf | A D36; M/Z D38 | automatisch | aus Vor-/Nachname ableiten; keine doppelte Pflege | B |
| Familienname `last_name` | A D48; M/Z D50 | MA, AG kann vorbereiten | N vor Einreichung | P; betriebliche Namensanzeige B |
| Vorname `first_name` | A AB48; M/Z AB50 | MA, AG kann vorbereiten | N vor Einreichung | P; betriebliche Namensanzeige B |
| Geburtsname `birth_name` | A D57; M/Z D59 | MA | B: abweichender Geburtsname; „nicht abweichend“ anbieten | P |
| Geburtsdatum `birth_date` | A AB57; M/Z AB59 | MA | N vor Einreichung; keine Jugend-/Mindestalterregeln aus Vorlage erfinden | P |
| Straße `street` | A D66; M/Z D68 | MA | N bei Standardanschrift; Ausnahmen ohne Straßennamen fachlich zulassen | P |
| Hausnummer `house_number` | A V66; M/Z V68 | MA | B: Anschrift besitzt Hausnummer; Text mit Zusätzen | P |
| Postleitzahl `postal_code` | A AB66; M/Z AB68 | MA | B: Land/Anschrift verwendet PLZ; Länderregeln separat | P |
| Ort `city` | A AF66; M/Z AF68 | MA | N vor Einreichung | P |
| Anschriftenzusatz `address_addition` | A D75; M/Z D77 | MA | O, falls für Zustellung nötig | P |
| SV-Nummer `social_security_number` | A AB75; M/Z AB77 | MA → AG | B/K: vorhanden; sonst gekennzeichneter Klärungsweg, keine erfundene Nummer oder pauschale Einreichungsblockade | S |
| Geburtsort `birth_place` | A D84; M/Z D86 | MA | K, insbesondere benötigte Meldedaten ohne bekannte SV-Nummer abstimmen | P |
| Geburtsland `birth_country` | A AB84; M/Z AB86 | MA | K wie Geburtsort; Länderkennung fachlich mappen | P |
| Geschlecht `gender_code` | A D93; M/Z D95 | MA | K: bestätigtes Meldeschema und Auswahlcodes; kein binärer Zwang | P |
| Staatsangehörigkeit `nationality_code` | A AB93; M/Z AB95 | MA | K vor Personal-/Abrechnungsfreigabe, unbekannt/ungeklärt nicht als deutsch voreinstellen | P |
| Schwerbehinderung `disability_status` | A D102; M/Z D104 | MA → AG | K: Zweck, Berechtigung und erforderlicher Nachweis zuerst klären; kein pauschales Pflicht-Upload | H |
| Arbeitnehmernummer Sozialkasse Bau `construction_worker_number` | A AB102; M/Z AB104 | AG | — für Tankstellenpilot; nur bei tatsächlich einschlägigem Verfahren | E |
| IBAN Gehaltskonto `salary_iban` | A D111; M/Z D113 | MA | B: Überweisung vereinbart; sonst alternativen Zahlungsweg klären; keine Kontoeigentumsprüfung behaupten | S |
| BIC Gehaltskonto `salary_bic` | A AB111; Z AB113; kein eigenes sichtbares M-Gehaltsfeld | MA | K/B: nur wenn Zahlungsweg benötigt; nicht allgemein verpflichtend | S |
| Abweichender Kontoinhaber `salary_account_holder` | A D120; M AB113; Z D122 | MA | B: Kontoinhaber weicht ab; eigener Name sonst ableitbar | S |

E-Mail/Telefon, Anschriftenland und Notfallkontakt sind **zusätzliche Nova-Angaben**, keine hier nachgewiesenen eigenen Felder der sichtbaren Bögen. E-Mail ist für den beauftragten E-Mail-Einladungsweg nötig, Telefon/Notfallkontakt zunächst optional und zweckbezogen. Anschriftenland separat vorsehen, damit ausländische Adressen nicht mit deutschen PLZ-Regeln abgewiesen werden.

## 2. Kinder und Elterneigenschaft

| Angabe / Schlüssel | Quellen | Eingabe | Wann erforderlich? | Sicht |
| --- | --- | --- | --- | --- |
| Elterneigenschaft `parenthood_status` | A AB319; Z AB290 | MA → AG | K: erforderliche Nachweise/Abrechnungsfall mit Kanzlei bestimmen | H |
| Familienname je Kind `children[].last_name` | A D132/D141/D150/D159/D168; Z D134/D143/D152/D161/D170 | MA | B/K: erforderlicher Nachweisdatensatz zu Elterneigenschaft | H |
| Vorname je Kind `children[].first_name` | gleiche Zeilen, Spalte T | MA | B/K wie oben | H |
| Geburtsdatum je Kind `children[].birth_date` | gleiche Zeilen, Spalte AN | MA | B/K wie oben | H |

Kein entsprechender Kinderblock im sichtbaren M-Bogen nachgewiesen. Das bedeutet nicht automatisch, dass der Arbeitgeber in jedem Minijobfall keinerlei Angaben benötigt; zusätzlicher Bedarf muss gesondert bestätigt werden. Kinderfreibetrag ist ein separates Abrechnungsfeld und nicht aus dieser Liste berechnen. Fünf Vorlagenzeilen sind keine fachliche Obergrenze.

## 3. Beschäftigung, Ausbildung und Vertrag

| Angabe / Schlüssel | Quellen A / M / Z | Eingabe | Wann erforderlich? | Sicht |
| --- | --- | --- | --- | --- |
| Eintritt `employment_started_on` | A D180; M D125; Z D182 | AG | N vor Arbeitgeberfreigabe | V; freigegebener betrieblicher Zeitraum B |
| Ersteintritt `first_started_on` | A P180; M P125; Z P182 | AG | K/B bei abweichendem früherem Eintritt; nicht automatisch identisch setzen | V |
| Beschäftigungsbetrieb `employment_establishment_id` | A AB180; M AB125; Z AB182 | AG | N vor Arbeitgeberfreigabe; Betriebs-/Stationsbezug fachlich bestätigen | B |
| Berufsbezeichnung `profession_title` | A D189; M D134; Z D191 | AG, MA kann Auskunft liefern | N vor Arbeitgeberfreigabe | V; notwendige Tätigkeit B |
| Tätigkeit / BA-Kennziffer `occupation_code` | A AB189; M AB134; Z AB191 | AG | K vor Abrechnungsfreigabe; Text und Code unterscheidbar speichern | E |
| Haupt-/Nebenbeschäftigung `employment_priority` | A D198 | MA → AG | K, eigene Selbstauskunft und Arbeitgeberbeurteilung getrennt | S |
| Probezeit vereinbart `probation_agreed` | A AB198 | AG | N Entscheidung im allgemeinen Vertragsprofil; keine Standardlaufzeit erfinden | V |
| Dauer Probezeit `probation_duration` | A AN198 | AG | B: Probezeit vereinbart; Einheit explizit | V |
| Weitere Beschäftigungen vorhanden `has_other_employments` | A D207; M D334 als Block | MA | N Ja/Nein/noch zu klären für passende Profile; Details nur bei Ja | S |
| Weitere Beschäftigung geringfügig? `other_employment_is_marginal` | A AB207 | MA → AG | B: weitere Beschäftigung angegeben; genaue Bezugsfrage mit Kanzlei bestätigen | S |
| Höchster Schulabschluss `school_qualification_code` | A D216; M D143; Z D200 | MA | K, bestätigten Katalog einschließlich unbekannt verwenden | P |
| Höchste Berufsausbildung `vocational_qualification_code` | A AB216; M AB143; Z AB200 | MA | K, wie Schulabschluss | P |
| Ausbildungsbeginn `training_started_on` | A D225; Z D209 | AG | B/N bei Ausbildung | V |
| Voraussichtliches Ausbildungsende `training_expected_end_on` | A AB225; Z AB209 | AG | B/N bei Ausbildung; Verlängerung versionieren | V |
| Wochenarbeitszeit `weekly_hours` | A D234; M D152; Z D218 | AG | N vor Vertragsfreigabe; Sonderfall ohne feste Wochenstunden separat entscheiden | V; freigegebener Planungswert B |
| Jahresurlaubsanspruch `annual_vacation_days` | A P234; M P152; Z P218 | AG | N vor Vertragsfreigabe; Bezugsarbeitstage und Gültigkeit zusätzlich festlegen | V; erforderliches Planungskontingent B |
| Vertragsform `contract_form_code` | A AB234; M AB152; Z AB218 | AG | N: Voll-/Teilzeit und Befristung konsistent, Katalog prüfen | V |
| Wochenverteilung Montag bis Sonntag `weekly_distribution[]` | A D246/P246/AB246/AN246/D250/P250/AB250; M D164/P164/AB164/AN164/D168/P168/AB168 | AG | B: feste Verteilung vereinbart; „ggf.“ im Bogen, kein Pflicht-Schichtplan | V; freigegebene Verteilung B |
| Kostenstelle `cost_center` | A D256; M D174; Z D227 | AG | B: Arbeitgeber nutzt Kostenstellen | E |
| Abteilungsnummer `department_number` | A AB256; M AB174; Z AB227 | AG | B: Arbeitgeber nutzt Abteilungen | E |
| Baugewerbe beschäftigt seit `construction_started_on` | A D265; M D183; Z D236 | AG | — im Tankstellenpilot | E |
| Personengruppe `person_group_code` | A AB265; M AB183; Z AB236 | AG/Kanzlei | K vor Abrechnungsfreigabe; keine Ableitung allein aus Nova-Rollen oder Teilzeit | E |
| Status bei Beschäftigungsbeginn `initial_employment_status` | M D192 | MA → AG | K/N im M-Profil zur Arbeitgeberprüfung; passende Auswahlliste bestätigen | S |
| Art der Befristung `fixed_term_type` | A D277; M D204; Z D248 | AG | N Entscheidung unbefristet/befristet/Zweck; keine rechtliche Wirksamkeitsprüfung zusagen | V |
| Befristet bis `fixed_term_end_on` | A D286; M D213; Z D257 | AG | B: kalendermäßige Befristung; Zweckbefristung separat beschreiben | V |
| Abschlussdatum Vertrag `contract_concluded_on` | A AB286; M AB213; Z AB257 | AG | B: Vertrag abgeschlossen; unbekannt nicht durch Eintritt ersetzen | V |

Quellobjekte `cbx_Schriftlicher_Abschluss` und `cbx_Weiterbeschaeftigung` in A/Z nachgewiesen, aber Beschriftung/Antwortlogik dieser eingebetteten Steuerelemente noch nicht vollständig decodiert. Als offene Zusatzfragen registriert; daraus keine ungeprüften Nova-Pflichtfelder machen.

## 4. Steuer und Sozialversicherung

| Angabe / Schlüssel | Quellen A / M / Z | Eingabe | Wann erforderlich? | Sicht |
| --- | --- | --- | --- | --- |
| Steuer-ID `tax_id` | A D298; M D225; Z D269 | MA → AG | K/B je Abrechnungsverfahren; vorhanden liefern, fehlende ID als Klärfall | S |
| Steuerklasse `tax_class` | A AB298; M AB225; Z AB269 | AG/Kanzlei | B/K bei individueller Besteuerung; MA-Auskunft höchstens ungeprüfter Hinweis | S |
| Faktor `tax_factor` | A AN298; M AN225; Z AN269 | AG/Kanzlei | B/K bei anwendbarem Faktorverfahren | S |
| Kinderfreibetrag `child_tax_allowance` | A D307; M D234; Z D278 | AG/Kanzlei | B/K nach maßgeblichen Abrechnungsmerkmalen; nicht aus Kinderanzahl ableiten | S |
| Pauschalierung `flat_rate_tax_method` | M AB234 | AG/Kanzlei | K: Arbeitgeberentscheidung nach bestätigtem Beschäftigungs-/Steuerfall | S |
| Abwälzung Pauschalsteuer `flat_rate_tax_employee_charge` | M D243 | AG/Kanzlei | B/K nur bei einschlägiger Pauschalierung/vereinbartem Verfahren | S |
| Gesetzliche/letzte gesetzliche Krankenkasse `statutory_health_fund` | A D319; Z D290 | MA → AG | B/K nach Versicherungsfall; Kassenname allein ersetzt keine fachliche Kennung | S |
| Versicherungsart `health_insurance_type` | M D255 | MA → AG | K im M-Profil, gesetzlich/privat nach belegter Angabe | S |
| Kasse/private Versicherung `health_insurer_name` | M AB255 | MA | B/K nach Versicherungsart; nicht mit leerer pauschaler „Standardkasse“ füllen | S |
| KV-Beitragsgruppe `health_contribution_code` | A D328; Z D299 | AG/Kanzlei | K vor Abrechnungsfreigabe für anwendbares Verfahren | E |
| RV-Beitragsgruppe `pension_contribution_code` | A P328; Z P299 | AG/Kanzlei | K wie KV | E |
| AV-Beitragsgruppe `unemployment_contribution_code` | A AB328; Z AB299 | AG/Kanzlei | K wie KV | E |
| PV-Beitragsgruppe `care_contribution_code` | A AN328; Z AN299 | AG/Kanzlei | K wie KV; nicht aus Elterneigenschaft allein ableiten | E |
| UV-Gefahrtarif `accident_risk_tariff` | A D337; M D264; Z D308 | AG/Kanzlei | K nach Arbeitgeber-/UV-Verfahren, keine Mitarbeiter-Selbsteinstufung | E |
| DEÜV-Status `deuev_status_code` | A AB337; M AB264; Z AB308 | MA → AG/Kanzlei | K: zugrunde liegende Beziehung abfragen, Code Arbeitgeber bestätigen | E; eigene Tatsachenauskunft P |
| RV-Befreiungsoption `pension_exemption_request` | M D277 | MA erklärt Wunsch, AG prüft Nachweis/Wirksamkeit | B/K nur Minijob, nicht automatisch kurzfristig; kein vorausgewählter Antrag | S |

Konfession steht in versteckten Katalogtexten, ist in den ausgewerteten sichtbaren Feldbeschriftungen dieser drei Bögen kein eigenes nachgewiesenes Feld. Daher **kein automatisches Nova-Sammelfeld**. Die RV-Bogenformulierung wird als Quellenwortlaut behandelt; rechtliche Aktualität, Antragsform und Wirksamkeit müssen separat geklärt werden.

## 5. Entgelt und Ausbildungsvergütung

| Angabe / Schlüssel | Quellen | Eingabe | Wann erforderlich? | Sicht |
| --- | --- | --- | --- | --- |
| Entgeltbezeichnung je Bestandteil `pay_components[].label` | A D349/D358; M D286/D295 | AG | B/N sobald Bestandteil vereinbart | V + Sensitivrecht |
| Betrag je Bestandteil `pay_components[].amount` | A L349/L358; M L286/L295 | AG | B/N bei betragsbezogenem Bestandteil; Währung/Bezugsperiode klären | V + Sensitivrecht |
| Betrag gültig ab `pay_components[].valid_from` | A T349/T358; M T286/T295 | AG | B/N je Betrag; Quelle MM/JJJJ, keine erfundene Tagesgenauigkeit | V |
| Stundenlohn `pay_components[].hourly_rate` | A AB349/AB358; M AB286/AB295 | AG | B/N bei Stundenvergütung; Betrag und Stundenlohn nicht blind addieren | V + Sensitivrecht |
| Stundenlohn gültig ab `pay_components[].hourly_valid_from` | A AN349/AN358; M AN286/AN295 | AG | B/N je Stundenlohn; Quelle MM/JJJJ | V |
| Ausbildungsjahr `training_pay[].year` | Z D320/D329/D338 | AG | B/N bei Ausbildungsvergütung; drei Zeilen keine universelle Ausbildungsdauer | V |
| Ausbildungsbetrag `training_pay[].amount` | Z L320/L329/L338 | AG | B/N je vereinbartem Ausbildungsabschnitt | V + Sensitivrecht |
| Betrag gültig ab `training_pay[].valid_from` | Z T320/T329/T338 | AG | B/N je Abschnitt, MM/JJJJ | V |
| Ausbildungs-Stundenlohn `training_pay[].hourly_rate` | Z AB320/AB329/AB338 | AG | B/K nur falls vereinbart/benötigt, nicht zusätzlich automatisch abrechnen | V + Sensitivrecht |
| Stundenlohn gültig ab `training_pay[].hourly_valid_from` | Z AN320/AN329/AN338 | AG | B: entsprechender Stundenlohn vorhanden | V |

Vergütung nur einmal fachlich führen, mit Gültigkeit und Historie. Keine zweite Kopie im Mitarbeiter-Grundstamm. Keine Prüfung gegen aktuelle Mindestlöhne/Ausbildungsvergütungen oder automatische Grenzbeträge in diesem Dokument.

## 6. Vermögenswirksame Leistungen (VWL)

Der Bogen nennt ausdrücklich: **nur notwendig, wenn Vertrag vorliegt** (A D367; M D304; Z D347). Nova-Vorschlag: Frage „VWL-Vertrag vorhanden?“; bei Nein gesamte Gruppe entfallen lassen. Kein entsprechender Arbeitgeberzuschuss ohne Vereinbarung.

| Angabe / Schlüssel | Quellen A / M / Z | Eingabe | Wann erforderlich? | Sicht |
| --- | --- | --- | --- | --- |
| Empfänger `vwl.recipient` | A D370; M D307; Z D350 | MA liefert Vertrag → AG | B: VWL-Vertrag vorhanden | S |
| Betrag `vwl.amount` | A AB370; M AB307; Z AB350 | MA liefert Vertrag → AG | B: VWL-Vertrag vorhanden | S |
| AG-Anteil monatlich `vwl.employer_share` | A AN370; M AN307; Z AN350 | AG | B: VWL-Vertrag vorhanden; ausdrücklich 0 möglich, nicht fehlend | S |
| Beginn `vwl.started_on` | A D379; M D316; Z D359 | MA liefert Vertrag → AG | B: VWL-Vertrag vorhanden | S |
| Vertragsnummer `vwl.contract_number` | A AB379; M AB316; Z AB359 | MA | B: Vertrag besitzt Nummer; Ausnahmen klären | S |
| VWL-IBAN `vwl.iban` | A D388; M D325; Z D368 | MA | B: Zahlung auf Vertragskonto | S |
| VWL-BIC `vwl.bic` | A AB388; M AB325; Z AB368 | MA | B/K: Zahlungsweg benötigt BIC | S |

VWL-Konto strikt getrennt vom Gehaltskonto. Unterlagen nicht ohne Zweck in Stationsdokumente legen.

## 7. Weitere und frühere Beschäftigungen

| Angabe / Schlüssel | Quellen | Eingabe | Wann erforderlich? | Sicht |
| --- | --- | --- | --- | --- |
| Vorbeschäftigung Beginn `previous_jobs[].started_on` | A D400/D409; Z D380/D389 | MA | B/K: relevante steuerpflichtige Vorbeschäftigung im aktuellen Kalenderjahr | S |
| Vorbeschäftigung Ende `previous_jobs[].ended_on` | A L400/L409; Z L380/L389 | MA | B/K wie oben; laufender Job getrennt behandeln | S |
| Vorbeschäftigungsart `previous_jobs[].type` | A T400/T409; Z T380/T389 | MA → AG | B/K wie oben | S |
| Beschäftigungstage `previous_jobs[].days` | A AN400/AN409; Z AN380/AN389 | MA → AG | B/K: einschlägiger Beurteilungszeitraum; keine Zahl erfinden | S |
| Weiterer/früherer Job Beginn `other_jobs[].started_on` | M D338/D347 | MA | B: weiterer Job; bei kurzfristig auch relevante Vorbeschäftigungen des Kalenderjahrs | S |
| Job Ende `other_jobs[].ended_on` | M L338/L347 | MA | B: beendet/befristet; „laufend“ ausdrücklich zulassen | S |
| Arbeitgeber `other_jobs[].employer_name` | M T338/T347 | MA | B: Job angegeben | S |
| Tätigkeit `other_jobs[].activity` | M AF338/AF347 | MA | B: Job angegeben | S |
| Wochenstunden `other_jobs[].weekly_hours` | M AR338/AR347 | MA | B/K: Umfang bekannt/benötigt; variable Tätigkeit gesondert markieren | S |
| Entgeltgrenze bei Zusammenrechnung eingehalten `combined_earnings_assessment` | M D356, Arbeitgeberhinweis D360 | AG/Kanzlei nach MA-Auskunft | B/K bei einschlägiger Zusammenrechnung; kein Selbstfreigabe-Häkchen des Mitarbeiters | E |

Die zwei Vorlagenzeilen begrenzen nicht die Zahl der Beschäftigungen. Eine fehlende Einkommensangabe lässt sich nicht aus Wochenstunden bestimmen; zusätzliche Nachfragen nur zweckbezogen und mit Kanzlei festlegen. Status „keine weiteren Jobs“ bewusst bestätigen, nicht aus leerer Tabelle ableiten.

## 8. Ergänzungen für den Nova-Ablauf (nicht als DATEV-Quellfelder ausgeben)

| Angabe | Pflege | Pflichtbedingung | Sicht |
| --- | --- | --- | --- |
| Einladungsempfänger-E-Mail | AG vorbereitet, Empfänger prüft eigene Kontaktangabe | N vor E-Mail-Versand; Änderung widerruft alten Zugang und benötigt Identitätsprüfung | P |
| Stammstation / weitere Arbeitsstationen | AG | N Stammstation vor betrieblicher Aktivierung; weitere optional | B |
| Benutzerrolle / Stationsrechte | gesondert berechtigter AG | keine Voraussetzung zum Ausfüllen; Default keine Verwaltungsrechte | eigene Rechte sichtbar, Vergabe gesondert geschützt |
| Telefonnummer | MA | O, nur benötigter Kontaktweg | P; eigene Freigabe betrieblicher Kontaktdaten gesondert |
| Anschriftenland | MA | N für Länderprüfung | P |
| Notfallkontakt Name/Telefon/Beziehung | MA | O; Zweck und Information für Kontaktperson klären | P; Notfallzugriff separat regeln |
| Rückfrage, Bearbeitungsstatus, Freigabefassung | AG/System | N je jeweiligem Übergang | MA eigene Rückfrage/Status, Personalverwaltung vollständiger Prozess |
| Nachweise/Erklärungen | MA liefert, AG prüft | B/K nur erforderlicher Nachweis; Uploadumfang nicht aus versteckten Katalogen erzwingen | H/S/V nach Inhalt |

## 9. Stufenweise Vollständigkeit

1. **Entwurf:** keine komplette Akte verlangen; Speichern bekannter Werte erlauben.
2. **Einladung:** autorisierter Partner/Arbeitgeber, identifizierbare Person, geprüfter Empfänger und geplanter Fall erforderlich. Keine Bank-/Steuerwerte nötig, keine automatische Rechtevergabe.
3. **Mitarbeiter reicht ein:** eigene N-Felder und beantwortbare B-Fragen, fehlende Nummern/Nachweise als konkrete Klärfälle. AG-Felder und offene K-Fragen nicht als Mitarbeiterfehler ausgeben. Keine Abrechnungsreife behaupten.
4. **Arbeitgeber prüft:** eigene N-Felder, bedingte Gruppen, Klärfälle und Widersprüche bearbeiten; Rückfragen feldbezogen. Mitarbeiterangaben bleiben nachvollziehbar, Korrekturen versioniert.
5. **Personal-/Vertragsfreigabe:** bestätigtes Pflichtprofil vollständig, Rechte und Zuständigkeit erneut geprüft. Abrechnungsfreigabe ist eigener Zustand.
6. **DATEV-Übergabe:** noch nicht Bestandteil. Erst nach verifiziertem X-Status, Zielprogramm, Importformat, Feldcodes, Empfänger/Mandant und fachlicher Abnahme.

## 10. Offene Freigaben und Prüfungen

- LODAS oder Lohn und Gehalt; aktuelle Kanzleifassung der drei Vorlagen und gewünschter Übergabeweg bestätigen.
- Gespeicherte Zellkennzeichnungen sind in Abschnitt 11 verifiziert. Noch offen bleiben zusätzliche dynamische Prüfungen, aktive Steuerelement-Zustände und die erfolgreiche Verarbeitung im konkreten DATEV-Zielverfahren; keine Excel-/Makroabnahme behaupten.
- K-Felder, Auswahlcodes, zuständige Personalrolle und Zugriff auf besonders geschützte Nachweise bestätigen. Stationsleitung erhält standardmäßig keine persönlichen Finanz-/Steuer-/Kinder-/Gesundheitsangaben.
- Personalnummern-/Arbeitgeberzuordnung, mehrere Nationalitäten/Arbeitgeber, variable Wochenstunden, Zweckbefristung, lange Ausbildungsdauer und Nachweisausnahmen entscheiden.
- Validierung später gegen Grenz-/Fehlfälle testen: Datenverlust bei Rückfragen, unbekannt statt Nein, ausländische Adressen, führende Nullen, geänderte Bankdaten, weitere Jobs, rollen-/mandantenfremde Zugriffe und Ablauf des Tests.

Tatsächliche Prüfung P-040: Vorlagenzelltexte/-stile und eingebettete Objektmetadaten lesend untersucht, alle sichtbaren Feldgruppen gegenüber P-039 abgeglichen. Keine Makros ausgeführt, keine Originaldatei/Altprojekt-/Datenbank-/Konfigurationsänderung, keine Anwendungstests oder Mails. Nur Nova-Dokumentation erstellt. Kein Commit/Push.

## 11. Verifizierte Vorlagenkennzeichnung P-041

### Verfahren und Grenzen

Die früheren pauschalen X-Markierungen werden hier für die gespeicherten Zellrahmen ersetzt. Alle drei sichtbaren Blätter über Workbook-Bezug als `xl/worksheets/sheet2.xml` identifiziert. Zellstile aus `xl/styles.xml`, alle zusammengeführten Eingabezellen und die benachbarten Beschriftungen ausgewertet. Keine geänderte Datei gespeichert.

- Rot: Randfarbe `indexed="10"`, also Standard-Excel-Palettenfarbe Rot. Keine abweichende indizierte Palette im Styles-Teil vorhanden. Ein zuvor nur auf RGB-Text `FF0000` gerichteter Suchansatz konnte diese Rahmen nicht erkennen; diese Lücke ist jetzt geschlossen.
- Arbeitgeberbalken: linker Rand `style="thick"`, Farbe `indexed="8"` (Schwarz). Derselbe Stil ist direkt in der Vorlagenlegende links neben „vom Arbeitgeber auszufüllen“ nachgewiesen: A C9:C10, M/Z C11:C12. Feldbalken stehen links neben den jeweiligen Beschriftungs-/Eingabeblöcken, insbesondere in Spalten C, O, AA und AM.
- Alle **neun** roten Eingabebereiche je Datei besitzen einen geschlossenen roten Außenrahmen; Randsegmente außerhalb des Merge-Startfeldes mitberücksichtigt. Nicht nur rote Schrift/Einzelzelle oder Feldnamen gesucht. Je Vorlage 260 rote Randzellen. Schwarze Balken einschließlich Legende: A 238, M 206, Z 226 Zellen.
- Die Zuordnung ist eine strukturelle Prüfung der tatsächlich gespeicherten Grafikformatierung, keine Prüfung in laufendem Excel. Eingebettete ActiveX-Controls, spätere Makroänderungen und dynamische Exportprüfungen wurden nicht ausgeführt. „Nicht rot“ heißt daher **kein gespeicherter roter Zellrahmen**, nicht „DATEV akzeptiert immer einen leeren Wert“.

**Quellenstatus:** R = gespeicherter roter Exportpflichtrahmen; AG = schwarzer Arbeitgeberbalken; – = jeweilige Zellmarkierung nicht vorhanden. Für alle nicht in der folgenden Rot-Tabelle genannten normalen Eingabebereiche ist kein gespeicherter roter Zellrahmen nachgewiesen. Für die Vorlagenzuständigkeit ist die separate Arbeitgeber-Tabelle maßgeblich. Eine fehlende AG-Markierung ist keine automatische gesetzliche Mitarbeiterpflicht.

### 11.1 Alle roten Exportpflichtfelder

| Feld | A: Beschriftung → Eingabebereich | M: Beschriftung → Eingabebereich | Z: Beschriftung → Eingabebereich | Vorlagenkennzeichnung |
| --- | --- | --- | --- | --- |
| Personalnummer | AN36 → AN39:AV41 | AN38 → AN41:AV43 | AN38 → AN41:AV43 | R + AG, Balken AM35:AM42 bzw. AM37:AM44 |
| Familienname | D48 → D51:X53 | D50 → D53:X55 | D50 → D53:X55 | R, kein AG-Balken |
| Vorname | AB48 → AB51:AV53 | AB50 → AB53:AV55 | AB50 → AB53:AV55 | R, kein AG-Balken |
| Geburtsdatum | AB57 → AB60:AV62 | AB59 → AB62:AV64 | AB59 → AB62:AV64 | R, kein AG-Balken |
| Straße | D66 → D69:T71 | D68 → D71:T73 | D68 → D71:T73 | R, kein AG-Balken |
| Hausnummer | V66 → V69:X71 | V68 → V71:X73 | V68 → V71:X73 | R, kein AG-Balken |
| PLZ | AB66 → AB69:AD71 | AB68 → AB71:AD73 | AB68 → AB71:AD73 | R, kein AG-Balken |
| Ort | AF66 → AF69:AV71 | AF68 → AF71:AV73 | AF68 → AF71:AV73 | R, kein AG-Balken |
| Eintrittsdatum | D180 → D183:L185 | D125 → D128:L130 | D182 → D185:L187 | R, kein AG-Balken; AG pflegt in Nova dennoch den vereinbarten Eintritt |

Damit erhalten diese neun Feldzeilen in der Hauptmatrix den Quellstatus **R** in allen drei Varianten. Die bisherige Nova-Ausnahme für Adressen ohne Hausnummer beziehungsweise ohne PLZ bleibt ein zu lösender Konflikt zum konkreten Vorlagenexport: keine erfundenen Werte einsetzen, sondern vor späterem Export einen fachlich bestätigten Ausnahmeweg verlangen. Personalnummer ist erst zur späteren Freigabe nötig, nicht zur Einladung.

### 11.2 Arbeitgeberbalken: gemeinsame markierte Felder/Blöcke

Die Bereiche nennen die tatsächlich vorhandene linke schwarze Balkenlinie. Bei zusammengehörigen Entgeltblöcken gilt sie für die im selben Block liegenden Betrag-/Gültigkeitsfelder; dies ist als Block markiert, nicht als selbstständiger Balken an jedem Teilfeld.

| Feld / Block | A: Balken | M: Balken | Z: Balken | Konsequenz für Hauptmatrix |
| --- | --- | --- | --- | --- |
| Personalnummer | AM35:AM42 | AM37:AM44 | AM37:AM44 | AG; außerdem R |
| Schwerbehindert | C101:C108 | C103:C110 | C103:C110 | Vorlagenpflege AG; MA liefert nur zweckbezogene Angabe/Nachweis, geschützte Sicht H bleibt |
| Ersteintritt | O179:O186 | O124:O131 | O181:O188 | AG |
| Beschäftigungsbetrieb | AA179:AA186 | AA124:AA131 | AA181:AA188 | AG |
| Ausgeübte Tätigkeit / Kennziffer | AA188:AA195 | AA133:AA140 | AA190:AA197 | AG |
| Wochenarbeitszeit | C233:C240 | C151:C158 | C217:C224 | AG |
| Urlaubsanspruch | O233:O240 | O151:O158 | O217:O224 | AG |
| Vertragsform | AA233:AA240 | AA151:AA158 | AA217:AA224 | AG |
| Kostenstelle | C255:C262 | C173:C180 | C226:C233 | AG |
| Abteilungsnummer | AA255:AA262 | AA173:AA180 | AA226:AA233 | AG |
| Personengruppe | AA264:AA271 | AA182:AA189 | AA235:AA242 | AG/Kanzlei |
| Art der Befristung | C276:C283 | C203:C210 | C247:C254 | AG |
| Ergänzender Befristungs-Controlblock rechts | AA276:AA283 | AA203:AA210 | AA247:AA254 | AG-Block; genauer Wortlaut/Antwortlogik der Controls noch separat klären |
| Befristung bis | C285:C292 | C212:C219 | C256:C263 | AG |
| Vertragsabschluss am | AA285:AA292 | AA212:AA219 | AA256:AA263 | AG |
| UV-Gefahrtarif | C336:C343 | C263:C270 | C307:C314 | AG/Kanzlei |
| DEÜV-Status | AA336:AA343 | AA263:AA270 | AA307:AA314 | AG/Kanzlei; MA kann zugrunde liegende Tatsachen liefern |
| VWL-AG-Anteil | AM369:AM376 | AM306:AM313 | AM349:AM356 | AG; andere VWL-Felder besitzen diesen Balken nicht |

### 11.3 Variantenspezifische Arbeitgeberbalken

| Feld / Block | Vorlage und tatsächlicher Balken | Zuordnung |
| --- | --- | --- |
| Probezeit vereinbart | A AA197:AA204 | AG |
| Dauer Probezeit | A AM197:AM204 | AG |
| Wochenverteilung (gesamter Block Mo–So) | A C242:C253; M C160:C171 | AG; keine pauschale Pflicht bei variabler Arbeitszeit |
| KV-Beitragsgruppe | A C327:C334; Z C298:C305 | AG/Kanzlei |
| RV-Beitragsgruppe | A O327:O334; Z O298:O305 | AG/Kanzlei |
| AV-Beitragsgruppe | A AA327:AA334; Z AA298:AA305 | AG/Kanzlei |
| PV-Beitragsgruppe | A AM327:AM334; Z AM298:AM305 | AG/Kanzlei |
| Pauschalierung | M AA233:AA240 | AG/Kanzlei |
| Abwälzung an beschäftigte Person | M C242:C249 | AG/Kanzlei |
| Entgeltbestandteil: Bezeichnung, Betrag und gültig ab | A C348:C355 / C357:C364; M C285:C292 / C294:C301 | AG-Block, alle drei Teilfelder |
| Stundenlohn und gültig ab | A AA348:AA355 / AA357:AA364; M AA285:AA292 / AA294:AA301 | AG-Block, beide Teilfelder |
| Ausbildungsjahr 1: Betrag und gültig ab | Z C319:C326 | AG-Block |
| Ausbildungsjahr 2: Betrag und gültig ab | Z C328:C335 | AG-Block |
| Ausbildungsjahr 3: Betrag und gültig ab | Z C337:C344 | AG-Block |
| Ausbildungsstundenlohn und gültig ab, Jahre 1–3 | Z AA319:AA326 / AA328:AA335 / AA337:AA344 | AG-Block |

Damit sind sämtliche gespeicherten schwarzen Balken außerhalb der Legende erfasst. Im M-Bogen sind keine eigenen KV/RV/AV/PV-Gruppenfelder mit diesen Balken vorhanden; nichts aus A/Z in M hineininterpretieren.

### 11.4 Abweichungen zwischen Vorlagenmarkierung und Nova-Vorschlag

| Angabe | Befund in den gespeicherten Vorlagen | Nova-Vorschlag / Entscheidung |
| --- | --- | --- |
| Eintritt | R, ohne AG-Balken | AG pflegt vereinbarten Eintritt, MA kann ihn sehen; ausdrückliche Nova-Entscheidung statt behaupteter Vorlagenvorgabe |
| Schwerbehinderung | AG-Balken, kein roter Rahmen | MA liefert erforderliche Angaben, ausschließlich berechtigte Personalstelle prüft/pflegt; kein Pflichtfeld für alle oder Stationsleitung |
| Rechtlicher Arbeitgeber/Firma | kein roter Rahmen und kein schwarzer AG-Balken am Firmenblock | Auswahl durch AG vor Einladung aus sicherem Partnerkontext; keine Mitarbeiterwahl eines fremden Mandanten |
| Berufsbezeichnung | kein AG-Balken | Arbeitgeberpflege in Nova empfohlen, Mitarbeiter kann eigene Qualifikation/Tätigkeit erläutern |
| Ausbildungsbeginn und voraussichtliches Ende | kein AG-Balken | Arbeitgeberpflege nach Vertrag bleibt Nova-Vorschlag |
| Steuer-ID, Steuerklasse, Faktor, Kinderfreibetrag | keine gespeicherten roten Rahmen oder schwarzen AG-Balken an diesen Feldern | MA liefert eigene ID; maßgebliche Abrechnungsmerkmale durch AG/Kanzlei bestätigen, keine frei erfundene Selbstfreigabe |
| Entgeltgrenzenbeurteilung M | kein schwarzer Zellbalken; D360 nennt Arbeitgeberprüfung ausdrücklich im Text | AG/Kanzlei entscheidet; separate textliche Arbeitgeberzuordnung, kein R |
| Übrige personenbezogene/Versicherungs-/Bank-/Kinderangaben und weitere Jobs | keine gespeicherten roten Rahmen oder schwarzen AG-Balken, soweit nicht oben aufgeführt | MA liefert Tatsachen nach Bedarf, AG prüft; fehlende Markierung ist keine pauschale freiwillige oder gesetzliche Pflichtangabe |
| Sonstige VWL-Angaben | kein AG-Balken außer am AG-Anteil | MA liefert Vertrag; AG bestätigt Zahlung, bedingt durch bestehenden VWL-Vertrag |
| Zusatzangaben E-Mail, Stationsrollen, Notfallkontakt | keine entsprechenden nachgewiesenen eigenen Vorlagenfelder | eigenständige Nova-Felder und Rechte, kein R-/AG-Status aus DATEV ableiten |

### 11.5 Konsequenz und Prüfnachweis

Die gespeicherte **grafische** Vorlagenmarkierung ist damit für alle drei Dateien zugeordnet. Die vorgeschlagenen Nova-Pflichtbedingungen und Sichtrechte bleiben davon getrennt. Keine Rechte durch einen schwarzen Balken ableiten und kein nicht rot markiertes Feld automatisch für verzichtbar erklären. Weitere Exportbedingungen, insbesondere ActiveX-/Makrovalidierung, Mapping und Zielprogramm, sind weiterhin eigene offene Prüfungen.

Dateistand SHA-256 (nur Quellenidentifikation):

- A: `aeba6dcd2a15a40f75dcd93ce83666c3368223e3e2f60c482adeebb6da9b9b50`
- M: `998cff6c6acfa6530544e69e45ddbe66381ec142147fd2a236c209b34c897c77`
- Z: `4f583be8a00832ec38c3275f775a5b0ef46803c7a7215ce3249fa86add91de94`

Tatsächlich geprüft: Zellstil-Verknüpfung, Standard-Palettenindices, vollständige Merge-Außenrahmen, alle Arbeitgeberbalken und deren Vorlagenlegende sowie Feld-/Blockzuordnung. Keine visuelle Laufzeitabnahme in Excel, keine Makroausführung oder Änderung an Originaldateien/Altprojekt, keine Programmierung, keine Tests/Mails, kein Commit/Push.

## Umsetzungshinweis P-042

Erster lokaler Ablauf implementiert: Katalog `web/app/Employees/EmployeeFields.php`, vier Fallprofile, private Selbstauskunft und getrennte Inhaberprüfung. Einladungsdaten und Stammstation in Voranlage; Arbeitgeberdaten erst später. Entgelt-/Kinder-/Beschäftigungsgruppen wiederholbar, VWL bedingt; Bau-/Malerfelder, unklare Steuerelementfragen, Nachweisuploads/Signaturen nicht ungeprüft eingebaut. Aktuelle, ungeklärte Schlüssel werden als zu prüfende Texte erfasst und nicht als DATEV-validierte Codes ausgegeben. Internationale Anschriftenausnahmen bleiben gegenüber späterem DATEV-Export zu klären. Bestätigte Fassungen werden in diesem Paket nur gelesen. Konkrete Grenzen und tatsächliche Prüfungen im Leitfaden P-042.

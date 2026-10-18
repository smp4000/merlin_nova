# Merlin Nova Bauplan Web App und SaaS

Stand: 18.10.2026. Fachlicher und technischer Plan, noch keine implementierte neue Anwendung. Fortschritt im [Leitfaden](../MERLIN-NOVA-LEITFADEN.md).

## Drei klar getrennte Oberflächen

Die öffentliche Landingpage erklärt den Nutzen, die Einrichtung und das Abo. Der Partnerbereich dient dem Betrieb der eigenen Firma und Stationen. Der Superadminbereich steuert die Plattform, Partner, Tarife und Abrechnung. Ein Partner erhält niemals durch einen versteckten Menüpunkt Zugriff auf Verwaltungsfunktionen der Plattform.

## W01 Landingpage

Eigene Gestaltung statt Standard-Adminmaske. Klare Hauptaktion „Partner werden“, gut sichtbare Anmeldung und verständliche Erklärung des Ablaufs Partner → Stationen → Mitarbeiter. Funktionen erläutern: Mitarbeiter/Verträge, Einsatzplanung, MHD/Abschriften/Etiketten, Kassenschichten/Übergaben, Gratisabgaben und Störungen. MDE und Druckagent als zusammenhängenden Arbeitsweg erklären.

Tarifinformationen werden aus dem veröffentlichten Katalog des Superadmins geliefert, nicht in der Landingpage hardcodiert. Noch nicht freigegebene Funktionen werden nicht als verfügbar verkauft. Keine erfundenen Kundenlogos, Bewertungen, Einsparversprechen oder Datenschutz-Zertifikate. Impressum, Datenschutz, Kontakt sowie Vertragsinformationen müssen vor Veröffentlichung fachlich fertig sein.

**Abnahme:** Desktop und Mobil verständlich; Registrierung und Login erreichbar; veröffentlichte Tarifversion erkennbar; Entwurfstarife erscheinen nicht öffentlich; keine unnötigen Drittanbietertracker.

## W02 Registrierung und Partnerkonto

1. Firmenname, Ansprechpartner, geschäftliche E-Mail und Passwort erfassen.
2. Im Registrierungsschritt SEPA-Daten und Mandat für eine mögliche spätere kostenpflichtige Nutzung erfassen. Klar anzeigen: keine Abbuchung im Test, keine automatische Umwandlung und kein kostenpflichtiger Abschluss durch das Mandat allein. Mandatstext, Gläubigerangaben und Nachweisverfahren vor Veröffentlichung gesondert prüfen.
3. Eigenen Mandanten und erstes Inhaberkonto eindeutig als Einrichtung anlegen; doppelte Formulareinsendung darf keinen zweiten Mandanten erzeugen. E-Mail bestätigen; abgelaufene und erneut versendete Links sicher behandeln.
4. Kostenlose Testphase nach E-Mail-Bestätigung starten; Dauer aus der veröffentlichten Superadmin-Einstellung übernehmen und Beginn/zugesagtes Ende beim Partner festhalten. Wiederholte E-Mail-Bestätigung startet keinen neuen Testzeitraum.
5. Durch die Einrichtung führen: erste Station, erste Mitarbeiter, erste Planung im freigegebenen Testumfang.
6. Den kostenpflichtigen Abschluss als getrennten, bewusst ausgelösten Ablauf anbieten: Tarif wählen, Rechnungsdaten erfassen, bereits erteiltes SEPA-Mandat erneut auf gültigen Status prüfen und bei Bedarf neu erfassen, Preis/Laufzeit/Beginn prüfen und ausdrücklich kostenpflichtig abschließen.

Beschlossen am 18.10.2026: zuerst kostenlos testen, Testdauer und Tarife im Superadmin frei einstellen, anschließend kostenpflichtiges Abo ausschließlich ausdrücklich mit gültigem SEPA-Mandat abschließen. Keine automatische Umwandlung am Testende, keine Rechnung oder Lastschrift allein aufgrund einer Registrierung oder eines Testablaufs. Auch ein bereits hinterlegtes Mandat ersetzt keinen Vertragsabschluss.

Bestätigt am 18.10.2026: Testbeginn nach E-Mail-Bestätigung, nach Testende ohne Abschluss lesender Zugang mit klarer Abschlussmöglichkeit, keine sofortige Datenlöschung. Das SEPA-Mandat wird bereits bei Registrierung erfasst, jedoch ohne kostenpflichtigen Abschluss nicht für einen Einzug verwendet. Regeln einer administrativen Testverlängerung bleiben offen. Änderungen der Standard-Testdauer verändern bereits zugesagte Testzeiträume nicht stillschweigend; bewusste Einzeländerungen werden begründet und protokolliert. Umfang und Stationsgrenzen des Tests sind getrennt von der Testdauer konfigurierbar zu planen, bedürfen aber einer fachlichen Festlegung.

Zustände für Konto/Einrichtung und Abo getrennt halten. Vorgeschlagene Abozustände: Test aktiv, Test abgelaufen ohne Abschluss, kostenpflichtiger Abschluss begonnen, kostenpflichtiges Abo aktiv, beendet. Ein Zahlungsproblem ist nicht dasselbe wie ein abgelaufener kostenloser Test. Wiederaufnahme ohne doppelte Registrierung ermöglichen. Zustimmungen mit Fassung und Zeitpunkt nachweisbar speichern; Marketingeinwilligung nicht mit verpflichtenden Vertrags-/Datenschutzinformationen vermischen. Die Rechtsgrundlage wird nicht durch einen pauschalen „DSGVO akzeptieren“-Haken ersetzt.

**Abnahme:** Wiederaufnahme, Doppelklick, nicht bestätigte E-Mail, veralteter Link, bereits vergebene Adresse, Abbruch und Rollback getestet. Öffentliche Endpunkte rate-limitiert; keine unnötige Kontoauskunft bei Login/Passwortreset.

## W03 Superadmin und Aboverwaltung von Anfang an

Der erste Produktabschnitt umfasst bereits:

- Partnerliste mit Einrichtungs-, Abo- und Rechnungsstatus.
- Tarifentwürfe, Veröffentlichung und datierte Tarifversionen.
- Frei pflegbare Preise, Laufzeiten, Abrechnungsintervalle, Module und Stationsgrenzen.
- Frei einstellbare kostenlose Testdauer; tatsächlicher Beginn und zugesagtes Ende je Partner nachvollziehbar. Keine automatisch kostenpflichtige Verlängerung.
- Abos mit eindeutigem Vertragspartner und historisch nachvollziehbarem Preisstand.
- Rechnungsübersicht, Forderungsstatus, Mandatsstatus und SEPA-Exportübersicht.
- Betriebsgesundheit: fehlgeschlagene Hintergrundjobs, E-Mailversand, Geräte-/Agentenstatus und Sicherheitsereignisse.
- Gesonderte Rollen für Plattformbetrieb, Support und Abrechnung als Vorschlag statt dauerhaft gemeinsamem Vollzugriff.

Eine Preisänderung im Katalog verändert bestehende Verträge nicht rückwirkend. Neuverträge und Vertragsänderungen verwenden festgehaltene Tarifversionen. Preis pro Station plus optionale Module ist ein Vorschlag, keine beschlossene Preisliste. Abzurechnende Stationszahl, Aktivierung/Stilllegung, Mindestanzahl, anteilige Zeiträume, Rabatte und Kündigungsregeln müssen vor produktiver Berechnung festgelegt werden.

Technischer Vorschlag: `Plan`, `PlanVersion`, `Subscription`, `SubscriptionItem`, `BillingPeriod`, `Invoice`, `InvoiceLine`, `CreditNote`, `Payment`, `DirectDebitMandate` und `DirectDebitBatch`. Bezeichnungen beschreiben Zuständigkeiten; keine ungeprüfte Liste bereits angelegter Tabellen.

### Vertragsseite und Kündigung

Bestätigt: gut sichtbarer Button „Abo kündigen“, Kündigung zum Ende des bezahlten Abrechnungszeitraums. Vor Bestätigung konkretes Enddatum, aktuellen Zugang und finanzielle Auswirkungen eindeutig darstellen. Bestätigte Kündigung protokollieren und dem Partner nachweisbar bestätigen; doppelte Bestätigung erzeugt keinen weiteren Vorgang. Bis zum bestätigten Termin bleiben bezahlte Berechtigungen erhalten, anschließend keine weitere automatische kostenpflichtige Periode. Kündigung und gleichzeitig laufende nächste Abrechnung müssen transaktional koordiniert werden.

Kündigung ist kein sofortiges Löschen des Mandanten, seiner Mitarbeiter oder Belege und kein Erlass bereits entstandener Forderungen. Mandatswiderruf ist ein eigener Vorgang; kein Einzug ohne die jeweils notwendigen gültigen Voraussetzungen. Rechtliche Vertrags-/Mandatsformulierungen und Aufbewahrungs-/Löschfristen vor Produktivbetrieb prüfen. Ein erster [Bildentwurf für Vertragsübersicht, Abschluss und Kündigungsbestätigung](mockups/partner-contract-v1.png) liegt vor, ist aber weder abgenommen noch implementiert.

### Entwurf der Superadmin-Bedienung

Ein erster [Bildentwurf für Tarife und Testphase](mockups/superadmin-subscriptions-v1.png) zeigt die zentrale Testdauer und eine übersichtliche Tarifliste. Die frei einstellbaren 30 Tage sind nur ein Beispiel. Teststart nach E-Mail-Bestätigung, lesender Zugriff nach Ablauf sowie ausdrücklich erforderlicher kostenpflichtiger Abschluss sind beschlossene Produktregeln, keine versteckten Schalter für automatische Abbuchung.

Tarife als Entwurf bearbeiten, vor Veröffentlichung Pflichtangaben/Preis-/Laufzeitkonsistenz prüfen. Ein im Mockup als „XX,XX“ dargestellter Preis ist nicht veröffentlichungsfähig. Veröffentlichte Fassungen nicht überschreiben: „Neue Fassung“ erzeugt eine gezielt freizugebende Änderung; vorhandene Verträge behalten ihre vereinbarten Datenstände. Unbefristete Einzel-Testverlängerungen oder rückwirkende Änderungen bestehender Zusagen sind nicht automatisch durch die globale Dauer-Einstellung freigegeben.

Die übrigen Adminansichten für einzelne Partnerabos, Rechnungen und SEPA-Dateien bleiben getrennte Arbeitsflächen, nicht Untertabs einer riesigen Einstellungsmaske. Kontodaten dort standardmäßig maskieren. Abrechnung und Lastschriftexport benötigen eigene Rechte sowie eine geprüfte Vorschau; ein Modellbild eines aktiven Schalters ist keine technische Freigabe.

## W04 Rechnungen und Zahlung

Rechnungen werden aus feststehenden Vertrags-/Leistungsdaten erzeugt. Für jede Aboperiode gibt es eine eindeutige Verarbeitung, mit Transaktion und Datenbank-Eindeutigkeit gegen doppelte Schedulerläufe. Vorschau, Belegnummer, Rechnungs-/Leistungszeitraum, Empfängerdaten, Steuerbehandlung, Positionen und Summen müssen nachvollziehbar sein.

Entwurf und ausgestellter Beleg sind getrennt. Ausgestellte Belege nicht frei überschreiben oder löschen; Korrekturweg über nachvollziehbare Gegen-/Korrekturbelege festlegen. PDF allein nicht vorschnell als vollständige Rechnungsanforderung ansehen: konkret erforderliche elektronische Rechnungsformate, Pflichtangaben und Aufbewahrung vor Produktivfreigabe fachlich prüfen lassen.

Belegstatus und Zahlungsstatus getrennt halten. Ein erstellter SEPA-Export beweist keine erfolgte Zahlung. Offene, teilweise oder vollständig bezahlte Forderungen, Rücklastschriften und manuelle Zuordnung abbilden. Versand idempotent und nachvollziehbar, mit ausdrücklich ausgelöstem erneutem Versand statt unkontrollierter Duplikate.

**Abnahme:** Zwei parallele Abrechnungsläufe erzeugen nicht zwei Rechnungen; Rundung, Monats-/Jahreswechsel, Tarifwechsel, Kündigung und Rücklastschrift mit festen Testfällen; technische Wiederholung ändert keine ausgestellte Rechnung.

Zusätzliche Abnahme für Test und Vertragsabschluss: Testablauf erzeugt trotz vorliegendem Mandat keine Rechnung und keinen Lastschriftauftrag; E-Mail-Bestätigung startet den Test nur einmal; nach Ablauf sind fachliche Schreibaktionen auch über API/MDE gesperrt, Abschluss und Kontoverwaltung bleiben gezielt möglich. Standard-Testdaueränderung verändert bestehende Zusagen nicht; ausdrücklicher Abschluss ohne gültiges Mandat wird abgewiesen; gültiges Mandat allein aktiviert kein Abo; paralleler Abschluss erzeugt nur einen Vertrag. Änderungen an Tarifpreis oder Mandatsstatus während des Abschlusses müssen erkannt werden, bevor eine Kostenpflicht bestätigt wird. Kündigung vor Periodenende erhält den bezahlten Zugang bis zum Enddatum, verhindert die nächste Periode und bewahrt erforderliche Belege; Parallelität mit Abrechnung und wiederholte Kündigungsbestätigung werden getestet.

## W05 SEPA mit VR Bank

### Gemeinsame Bankdaten-Komponente mit Rechner und Prüfer

Bei Registrierung, späterer Änderung der Partnerbankverbindung sowie berechtigten weiteren Bankdatenformularen dieselbe geprüfte Komponente einsetzen, ohne deren jeweilige Sicht-/Änderungsrechte zu erweitern. Direkte IBAN-Eingabe bleibt möglich; die Berechnung aus BLZ/Kontonummer ist eine zusätzliche Hilfe, keine Pflicht und kein Ersatz für das Mandat.

- „IBAN prüfen“ kontrolliert Länderformat, Länge und Prüfziffer; Eingabe normalisieren und verständlich formatieren, führende Nullen erhalten. Unterstützte Länder anhand aktueller Regeln festlegen, nicht auf deutsche Konten beschränken.
- „IBAN berechnen“ zunächst für Deutschland: Bankleitzahl und Kontonummer, Ergebnis erst nach bewusster Übernahme ins IBAN-Feld. Bankabhängige Sonderregeln und Gültigkeitsstände berücksichtigen; keine pauschale Aneinanderreihung mit Prüfziffer als verlässlich ausgeben. Quelle: [IBAN-Regeln der Bundesbank](https://www.bundesbank.de/de/aufgaben/unbarer-zahlungsverkehr/serviceangebot/iban-regeln).
- Bankname und gegebenenfalls BIC aus einer verlässlichen, versionierten Bankdatenquelle ergänzen, sofern verfügbar; fehlende Zuordnung ist keine bestätigte Kontoungültigkeit. Bei nicht unterstützter Berechnungsregel klar auf die von der Bank ausgestellte IBAN verweisen.
- Eine erfolgreiche formale Prüfung bestätigt weder Kontoexistenz, Kontoinhaber, Deckung noch Lastschriftfähigkeit. Keine grüne „Konto verifiziert“-Anzeige und keine Abbuchung zur stillen Prüfung auslösen. Eine echte Konten-/Inhaberprüfung wäre eine gesondert zu vereinbarende Bankintegration.
- Verbindlich erneut serverseitig prüfen. Bankdaten weder als URL-Parameter noch in Protokollen/Analysewerkzeugen speichern; nicht ohne vereinbarte Grundlage an fremde Prüfdienste senden. BLZ/Kontonummer als Rechenhilfen nicht unnötig zusätzlich dauerhaft speichern.
- Abnahme: falsche Prüfziffer, gültige formale IBAN ohne Existenznachweis, Länderformat, Leerzeichen/Kleinschreibung, führende Nullen, unbekannte/umgestellte BLZ, unterstützte und nicht unterstützte Sonderregeln, veralteter Datenbestand, bewusste Ergebnisübernahme und direkte API-Manipulation. Ausschließlich veröffentlichte/synthetische Testwerte verwenden.

Die gültige formale IBAN und ein gültiges Mandat sind getrennte Voraussetzungen. Keine Berechnung oder Prüfung schließt ein kostenpflichtiges Abo ab.

Für zahlende Mandanten ist ein gültiges Mandat vorgesehen. Benötigt werden unter anderem eindeutige Mandatsreferenz, Gläubigerbezug, Schuldner-/Kontodaten, Nachweis, Fassung, Erteilung, Widerruf und Verknüpfung zum Vertrag. Wie ein wirksamer digitaler Mandatsnachweis konkret erbracht wird, muss mit Bank/Rechtsberatung abgestimmt werden; ein beliebiger Checkboxzustand genügt nicht als pauschale Zusage.

Exports erfolgen zunächst bewusst durch berechtigte Personen, nicht als automatische Bankeinreichung. Kontodaten geschützt speichern; vollständige Anzeige und Export gesondert berechtigen. Datei, Anzahl, Gesamtsumme, Fälligkeit, Einzelpositionen und Herkunft der Forderungen nachvollziehbar dokumentieren. Wiederholung eines Exports darf nicht unbemerkt einen weiteren aktiven Einzug derselben Forderung anlegen. Änderungen erzeugen eine neue nachvollziehbare Batchfassung.

Die vorliegende alte XML-Beispieldatei dient nur zur fachlichen Strukturreferenz. Keine enthaltenen echten Konto-/Personendaten übernehmen oder ins Git kopieren. Das Zielformat richtet sich nach der konkret verwendeten VR-Bank/Bankingsoftware und deren aktuellen Annahmeregeln. Die EPC-Customer-to-PSP-Richtlinie enthält `pain.008.001.08`; damit ist die Annahme durch die konkrete Bank noch nicht nachgewiesen. [EPC-Unterlagen](https://www.europeanpaymentscouncil.eu/what-we-do/epc-payment-schemes/sepa-direct-debit-sdd/sepa-direct-debit-core-rulebook-and).

**Abnahme:** XSD- und Geschäftsregelprüfung, Summen-/Anzahlkontrolle, Mandat widerrufen/fehlend, doppelte Forderung, Storno, Rücklastschrift und angenommener Import in einem freigegebenen Banktestprozess. Kein echter Lastschrifteinzug während lokaler Entwicklung.

## W06 Station anlegen als Assistent

Gestaltungsgrundlage: [Suche und Datenprüfung](mockups/station-wizard-search-v2.png), [Partnerfarbe und Abschluss](mockups/station-wizard-finish-v2.png). Grundgestaltung am 18.10.2026 vom Nutzer positiv bestätigt; dies ist noch keine technische Implementierung oder Abnahme. Der Assistent läuft im kostenlosen Test ohne kostenpflichtigen Aboabschluss. Farbwerte sind markennahe Vorschläge, keine Zusage offizieller Markenrichtlinien. Die gewünschte Farbe verändert keine gespeicherte Marke.

**Schritt 1 Station finden:** Suche nach Ort, Postleitzahl, Name oder Position, soweit der gewählte Dienst dies anbietet. Bei erforderlicher Umkreissuche zuerst Standort/Koordinaten bestimmen. Keine vorgetäuschte Volltextfähigkeit eines Anbieters. Standortfreigabe im Browser nur bewusst, nicht voraussetzen.

**Schritt 2 Daten prüfen:** Gewählte Station mit externer Kennung, Marke, Adresse und gegebenenfalls Koordinaten anzeigen; editierbare eigene Bezeichnung, interne Stationsnummer und Ansprechpartner ergänzen. Aktuelle Kraftstoffpreise nur mit Quelle und Abrufzeit, „geschlossen“, „nicht verfügbar“ und veraltet sauber unterscheiden. Ein unbekannter Preis ist nicht null Euro.

**Schritt 3 Darstellung und Betrieb:** Partnerfarbe vorschlagen, aber frei wählbar lassen; Stationszeitzone, Grunddaten und erforderliche Zuordnungen festlegen. Marke und Wunschfarbe sind unterschiedliche Daten. Namen wie ARAL oder Shell dienen nicht als Behauptung einer offiziellen Partnerschaft mit Merlin Nova.

**Schritt 4 Zusammenfassung:** Übernommene und manuelle Angaben, Aboauswirkung und eventuelle Stationsgrenze transparent zeigen. Erst Bestätigen legt die Station an. Manuelle Erfassung bleibt auch bei Anbieterausfall möglich.

Vor endgültiger Anlage Mandant, Benutzerrecht, Test-/Vertragsstatus, Dubletten und Stationsumfang serverseitig erneut prüfen. Wird der Test während des Assistenten beendet, keine weitere fachliche Anlage mehr zulassen; Wiederaufnahme nach ausdrücklichem Aboabschluss möglich machen. Zurücknavigation darf keine Station erzeugen und keine Eingaben verlieren. Doppelte Bestätigung liefert dieselbe angelegte Station. Erst nach erfolgreichem Speichern den nächsten Schritt „Mitarbeiter anlegen“ anbieten. Keine automatische Tariferweiterung oder Gebührenforderung durch eine Stationssuche oder einen Anlageversuch.

Die extern gefundene physische Station und die rechtliche Betreiberzuordnung sind getrennt. Eine Stationssuche beweist nicht, dass der registrierende Partner Betreiber ist. Dubletten, Betreiberwechsel, geteilte Datenquellen und eine gegebenenfalls notwendige Betreiberprüfung sind als Produktregeln festzulegen. Kein automatischer Zugriff auf fremde Mandantendaten bei gleicher externer Stations-ID.

**Abnahme:** Anbieterfehler, falsche Antwort, Rate-Limit, Timeout, keine Treffer, manuelle Anlage, Doppelklick und zwei gleichzeitige Anlagen nahe der Tarifgrenze. Provider-Schlüssel nur serverseitig; lokale Tests verwenden Fakes. Nutzungsbedingungen und eventuelle Quellenpflichten des Preis-Dienstes vor Integration prüfen.

## W07 Mitarbeiter und Verträge

Eine fachlich klare Personalakte mit einer Ebene Haupttabs:

| Haupttab | Daten und Aufgaben |
| --- | --- |
| Stammdaten | Personalnummer, Name, Geburt, erforderliche persönliche Angaben, Schul-/Berufsabschluss und Ausbildung |
| Kontakt | Anschrift, Kontaktwege und notwendiger Notfallkontakt |
| Beschäftigung | Eintritt/Austritt, Tätigkeit, Kostenstelle, weitere Beschäftigungen und Vorbeschäftigungen |
| Verträge | Aktueller Vertrag, Vertragsverlauf, Gültigkeit, Befristung, Probezeit soweit vertragsbezogen, Wochenstunden, Urlaub und Vergütung |
| Steuer und Versicherung | Steuer-/SV-/Kassen-/Bankangaben und erforderliche Kinderdaten mit gesonderten Rechten |
| Zusatzleistungen | Weitere Entgeltbestandteile und vermögenswirksame Leistungen |
| Stationen und Rechte | Mehrere Arbeitsstationen, Stammstation und getrennte Benutzerberechtigungen |
| Dokumente | Geschützte Nachweise, Verträge und Erklärungen; Datumsfelder ersetzen keine Unterschriften |
| Appzugang | Einladung, Konto-/Gerätebezug, Status und Widerruf |

Der konkrete DATEV-/Kanzleibogen bestimmt benötigte Daten, nicht einen zusätzlichen Sammel-Tab. Keine doppelte Pflege derselben Wochenstunden oder Vergütung an mehreren Stellen. Mitarbeiter können ohne Appkonto bestehen. Stationszuordnung ist keine automatische Vergabe von Verwaltungsrechten. Steuer-/Bankdaten nicht für normale Stationsleitung sichtbar machen.

Verträge sind zeitlich gültige Datensätze statt ständig überschriebener Stammdaten. Ein neuer Vertrag verändert keine bereits abgeschlossenen Abrechnungs-/Planungsstände stillschweigend. Abrechnungsvorerfassung und zertifizierter DATEV-Export sind unterschiedliche Leistungen; ein Export wird nur nach eigener Spezifikation zugesagt.

**Abnahme:** Mitarbeiter mit zwei Stationen, stationsübergreifender Wechsel, überlappende Verträge, rückwirkende Änderung, Austritt mit bestehenden Vorgängen, parallele Bearbeitung, sichere Dokumentdownloads und verbotener Zugriff auf sensible Felder.

## W08 Personaleinsatzplanung als Herzstück

Zuerst eine verständliche manuelle Wochenplanung: Station, Tag, Schicht/Zeitraum, benötigte Anzahl und Qualifikation, verfügbare Mitarbeiter, Abwesenheiten, Stundenbedarf und Vertragsstand. Ziehen/Zuordnen darf nicht die einzige Bedienmöglichkeit sein; Tastatur und verständliche Formulare anbieten.

Entwurf, Prüfung und Veröffentlichung unterscheiden. Änderungen an veröffentlichten Plänen werden versioniert und nachvollziehbar kommuniziert. Stationsübergreifende Doppelbelegung, Reise-/Wechselzeiten soweit relevant, Qualifikationen, Gültigkeit des Beschäftigungsverhältnisses und abgestimmte Arbeitszeitregeln serverseitig prüfen. Harte Sperren und begründet erlaubte Ausnahmen trennen; bestehende Zuordnung bei fehlgeschlagener Verschiebung erhalten.

Verfügbarkeit und Urlaubs-/Abwesenheitsprozess werden in dem Umfang aufgebaut, den eine brauchbare Planung braucht. Gesetzliche, tarifliche und betriebliche Regeln nicht aus Vermutungen hartcodieren; mit zuständigen Fachpersonen bestätigen und versioniert testen.

Spätere KI liefert erklärbare Vorschläge, keine ungefragte Veröffentlichung. Optimierung benötigt zuerst eine deterministische Regelprüfung. Gesundheits-/Religions-/Bankdaten gehören nicht in einen KI-Planungsprompt. Anbieter, Datenübermittlung, Beschäftigtendatenschutz und rechtliche Einordnung der konkreten KI-Nutzung werden vor Aktivierung geprüft.

**Abnahme:** Zweites Gerät bearbeitet denselben Plan; Verschiebung scheitert ohne Datenverlust; Mitarbeiter an zwei Stationen zeitgleich verplant; Sommerzeit/Nachtschicht; Abwesenheit nach Veröffentlichung; Nutzer ohne Veröffentlichungsrecht; nachvollziehbare Ausnahme.

## W09 MHD und Abschriften mit Etiketten

Artikel/Barcode, Warengruppen und stationsbezogene benötigte Daten bilden die Grundlage. Ein Rundgang hat einen klaren Start, Fortschritt, erfasste Mengen/Datumswerte und einen Abschluss. Scanner erkennt Artikel; unbekannte Barcodes werden gezielt vorgemerkt statt als beliebiger neuer Artikel angelegt.

Aus ablaufender Ware werden je freigegebenem Ablauf Kontrolle, Abschrift oder Etikettendruck ausgelöst. Gründe und Mengen sind nachvollziehbar; abgeschlossene Buchungen nicht durch Löschen verschwinden lassen. Barcode, Datumsformat, Artikeltext und Etikettengröße müssen mit dem Druckagenten übereinstimmen. Veränderte Vorlagen wirken nicht rückwirkend auf einen bereits erzeugten Druckauftrag.

**Abnahme:** Ein Artikel mit mehreren MHDs, ungültige Menge, bereits bearbeitete Ware, zwei Geräte, Offline-Rundgang und wiederholter Upload; Druckfehler verändert keine abgeschlossene Warenbuchung.

## W10 Kassenschichten und sicherer Schichtwechsel

Kasse, verantwortliche Person, Beginn/Ende, Zählung, Differenz und Übergabe getrennt modellieren. Ein Wechsel schließt den bisherigen Zustand und eröffnet den neuen nur in einer gemeinsamen validierten Transaktion. Scheitert die neue Zuordnung, bleibt die alte gültig. Die Anzahl zulässiger offener Schichten richtet sich nach der bestätigten fachlichen Kassenregel, nicht nach zufälligen UI-Filtern.

Ein Doppelklick, zweites MDE oder erneuter Request darf keine zweite Übergabe erzeugen. Autorisierung für Quell-/Zielstation, Kasse und Mitarbeiterbezug serverseitig prüfen. Nachträgliche Korrekturen benötigen Berechtigung, Grund und Historie. Offline darf eine Zählung als Entwurf erfasst werden; endgültiger Wechsel zunächst nur online mit bestätigtem Serverstand.

## W11 Gratisabgaben und Störmeldungen

Gratisabgaben: Art, Artikel/Leistung, Menge, Grund, Station, ausführende Person und Zeit. Begrenzungen, Freigaben und ggf. Belege sind tarif-/rollenbezogen fachlich festzulegen; Testfälle gegen wiederholte Übermittlung und unerlaubte Ausgabe. Gutschein-/Waschpass-/Kaffeepass-Sonderlogik wird vor Übernahme aus dem alten System einzeln freigegeben, nicht als stiller Zusatzumfang mitkopiert.

Störungen: betroffene Station/Anlage, Kategorie, Beschreibung, Priorität, Fotos, Zuständigkeit, Status und Verlauf. Meldung, Bearbeitung und Abschluss sind unterschiedliche Rechte. Gerätefotos bleiben geschützte Dateien. Fremde Firmen sehen weder Meldung noch Anhänge. Benachrichtigungen sind gezielt konfigurierbar, nicht ein zweites unübersichtliches Dashboard.

## Datenschutz und Freigabe

Datenschutzgerechte Voreinstellungen, minimal erforderliche Pflichtfelder, getrennte sensible Rechte, protokollierte zweckgebundene Zugriffe und ein umsetzbares Aufbewahrungs-/Löschkonzept. Konto sperren, Mitarbeiter austreten lassen, Daten anonymisieren und rechtlich aufzubewahrende Belege erhalten sind verschiedene Vorgänge.

Vor Pilotstart: Rollen der Beteiligten, AV-Vertrag, Datenschutzhinweise, Verarbeitungstätigkeiten, Dienstleister, Hosting-/Backuporte, Betroffenenanfragen, Vorfallprozess und gesetzliche Aufbewahrung mit qualifizierter Unterstützung prüfen. Weder ein fertiges Formular noch Verschlüsselung allein bedeutet DSGVO-Konformität.

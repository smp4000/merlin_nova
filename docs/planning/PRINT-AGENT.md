# Merlin Nova Bauplan Druckagent

Stand: 17.10.2026. Geplanter Windows-Agent; keine Installation oder Umkonfiguration vorhandener Drucksysteme. Status im [Leitfaden](../MERLIN-NOVA-LEITFADEN.md).

## Ziel und Grenze der Zusage

Web-App und MDE sollen Etiketten an freigegebene DYMO-Druckstellen senden können, ohne Browser-Druckdialog oder offene eingehende Internetports am Stationsrechner. Der Nutzer wünscht möglichst alle DYMO-Modelle. Das bedeutet eine erweiterbare Architektur und geprüfte Kompatibilitätsmatrix, **keine ungeprüfte Garantie für jedes je hergestellte Gerät**.

LabelWriter und D1-/LabelManager-Banddrucker sind unterschiedliche Medien-/Funktionsklassen. Netzwerk-, USB- und Windows-Freigabedruck unterscheiden sich ebenfalls. Aktuelle/ältere Treiber, unterstütztes Windows, CPU-Architektur und SDK-Funktionen bestimmen die tatsächliche Freigabe.

## D01 Modellfamilien und Adapter

| Familie / Anschluss | Geplanter Prüfweg | Freigabestatus |
| --- | --- | --- |
| LabelWriter 450-Familie einschließlich Twin Turbo | Installierter Treiber, geeigneter DYMO-Adapter, Rollenwahl und Etikettenformate prüfen | Offen |
| LabelWriter 550-Familie | Passender aktueller Connect-/Treiberweg; verfügbare Rolleninformationen prüfen | Offen |
| 4XL und 5XL | Große Medienformate, Skalierung und Adapterkompatibilität getrennt testen | Offen |
| LabelWriter Wireless / Netzwerk | Verbindung, Adresswechsel, Erreichbarkeit und Herstellerunterstützung prüfen | Offen |
| Ältere LabelWriter | Nur mit verfügbarer sicher nutzbarer Treiber-/SDK-Kombination freigeben | Offen |
| LabelManager / D1 | Separater Medien-/Bandadapter; nur wenn der fachliche Druckbedarf dies erfordert | Offen |
| Windows-Freigabedrucker | Benutzer-/Dienstkontext, Rechte und Spooler-Rückmeldung testen | Offen |

Der Hersteller bietet SDKs und technische Referenzen an. Seine Beispiele unterscheiden unter anderem Rollenwahl für Twin Turbo und modellabhängige Rollenstatusfunktionen; der Agent muss Fähigkeiten deshalb ermitteln statt sie für jeden DYMO vorauszusetzen. [DYMO SDK](https://www.dymo.com/online-support-sdk.html), [offizielle Connect-Beispiele](https://github.com/dymosoftware/DCD-SDK-Sample).

Ein Adapter kapselt Erkennung, Fähigkeiten, Vorlagen-/Dokumentübergabe und Ergebnis. Moderne und ältere Wege können getrennte Adapter benötigen. Kein universeller „drucke beliebige Bytes an jeden DYMO“-Ansatz. Windows-Standarddruck ist eine zu prüfende Alternative, kein pauschaler Ersatz für korrekte Etikettenmaße und Herstellerfunktionen.

## D02 Laufzeit und Installation

Vorschlag: C# mit unterstützter .NET-LTS, kleine verständliche Tray-Oberfläche und persistenter lokaler Auftragsjournalführung. Treiber-/SDK-Kompatibilität vor Festlegung der konkreten Version prüfen. Ein Hintergrunddienst ist nur sinnvoll, wenn die freigegebenen Herstellerkomponenten und Drucker im betreffenden Windows-Dienstkonto tatsächlich funktionieren. Andernfalls Druckworker im angemeldeten Benutzerkontext statt falscher Zusage eines problemlosen Systemdiensts.

Installation mit nachvollziehbarem Herausgeber, Updatepaketen und möglichst signierten Artefakten; keine dauerhaften Administratorrechte im normalen Betrieb. Voraussetzungen sichtbar prüfen: Laufzeit, passende Treiber, Drucker, Format, Verbindung und Backendadresse. Installer verändert keine vorhandene Live-Agentenkonfiguration.

PC aus, im Ruhezustand oder Benutzerworker nicht angemeldet bedeutet nicht erreichbar. Diesen Zustand Web/MDE erklären; ein Server kann keinen ausgeschalteten USB-PC zum Drucken zwingen.

## D03 Sichere Registrierung

Agent im Partnerbereich anlegen und per kurz gültigem Einmalcode aufnehmen. Eindeutiger Mandanten-/Stationsbezug, widerrufbare Agentenidentität und nur notwendige Rechte. Kein Superadminpasswort und kein Datenbankzugang im Agenten.

Geheimnisse geeignet im Windows-Kontext schützen, keine Klartext-Zugangsdaten in Konfigurationsdateien/Logs. Ausgehend nur HTTPS zum freigegebenen Backend. Keine pauschalen Firewallfreigaben, keine Fernsteuerung und keine beliebigen Befehle vom Server. Lokaler Testagent erhält ausdrücklich eine separate Testidentität.

## D04 Druckstellen verwalten

Agent meldet erkannte Druckwarteschlangen und soweit verfügbar Modell/Fähigkeiten. Der Partner aktiviert bewusst eine Druckstelle, ordnet Station, Namen, erlaubte Vorlagen/Medien und Etikettenmaße zu. Nicht jeder auf Windows installierte Drucker erscheint automatisch im MDE.

Eigene Druckstellen-ID von veränderlichem Windows-Druckernamen trennen. Umbenennung, fehlendes Gerät oder unklarer Fingerabdruck wird als Konfigurationsproblem angezeigt, nicht stillschweigend auf irgendeinen Drucker umgelenkt. Bei mehreren passenden Zielen klare Auswahl beziehungsweise bewusst konfigurierte Vorgabe. Die Auswahl muss sowohl Berechtigung als auch Medien-/Vorlageneignung berücksichtigen.

## D05 Auftrag vom Klick bis zum Etikett

1. Web/MDE erzeugt über eine berechtigte Fachaktion einen Druckauftrag mit eindeutiger ID.
2. Backend hält Geschäftsbezug, Vorlage und Version, Werte, Anzahl, Medienformat, Ziel und Ersteller fest.
3. Inhalt/Format und erforderliche Barcodes werden geprüft. Exakte Dokumentfassung beziehungsweise renderbarer Datenstand wird unveränderlich fixiert.
4. Passender Agent beansprucht den Auftrag atomar mit begrenzter Lease, damit nicht zwei Agenten denselben Auftrag gleichzeitig übernehmen.
5. Agent lädt nur den autorisierten Inhalt, prüft Bindung/Hash und persistiert ihn im lokalen Journal.
6. Adapter übergibt an den vorgesehenen Drucker und hält Windows-/SDK-Referenzen soweit verfügbar fest.
7. Agent meldet den tatsächlichen technischen Status; Web und MDE zeigen denselben Zustand.

Vorgeschlagene Zustände: bereit, übernommen, lokal gesichert, an Drucksystem übergeben, fehlgeschlagen, Ausgang unklar, abgebrochen. Ein vom Spooler angenommener Auftrag ist **kein Beweis**, dass das Etikett physisch lesbar auf dem Tisch liegt. Eine „gedruckt“-Anzeige braucht eine ehrlich definierte technische Bedeutung.

## D06 Doppelverarbeitung und unbekannter Ausgang

Journal mit eindeutiger Auftrags-ID über Neustarts hinweg erhalten. Derselbe Auftrag wird nicht durch erneutes Polling neu angelegt. Claims/Quittungen und Statusmeldungen idempotent ausführen. Wiederanlauf zwischen Download, Journal und Spoolerübergabe gezielt prüfen.

Nach Übergabe an den Spooler kann bei einem Absturz unklar bleiben, ob Papier ausgegeben wurde. **Keine technische Exactly-once-Garantie für physisches Drucken behaupten.** In diesem Zustand nicht automatisch nochmals drucken, sondern kenntlich machen und eine berechtigte Bedienentscheidung verlangen. Wenn der konkrete Treiber eine sichere Auskunft ermöglicht, gezielt abgleichen.

Nachdruck ist ein neuer protokollierter Druckauftrag mit Bezug zum Original, Grund und eigenem Recht. Bei werttragenden Codes keinen neuen Gutscheinwert erzeugen, nur weil Papier erneut gebraucht wird; Ausgabe-/Einlösemodell und Nachdruckregeln fachlich getrennt freigeben.

## D07 Vorlagen und Vorschau

Ein definierter Vorlagentyp für den ersten freigegebenen Etikettenprozess, anschließend erweiterbare Vorlagen. Maße in Millimetern, Druckbereich, Rotation, Schrift, Barcodeart, Barcode-Ruhezonen und Mengen präzise festlegen. Serverseitige Vorschau und Agentenausgabe müssen auf dieselbe versionierte Spezifikation zurückgehen.

Fremde DYMO-Vorlagendateien nicht ungeprüft ausführen: erlaubte Elemente und Größen validieren, externe Pfade/URLs und unerlaubte Inhalte ausschließen. Render-/Dateigrößen begrenzen. Falls Dateiformate importiert werden, braucht jeder Importweg eigene Tests.

Physischer Barcode-Scan mit dem echten MDE gehört zur Abnahme. „Sieht auf dem Bildschirm gut aus“ genügt nicht. Unterschiedliche Auflösung, Rolle, Treiber und Skalierung können eigene Profile erfordern. Kein stilles „An Seite anpassen“, das Barcodes oder Maße verändert.

## D08 Fehler und Updates

Verständliche Kategorien: Agent offline, Drucker fehlt, Format unpassend, Materialproblem soweit erkannt, Zugriff verweigert, Vorlage ungültig, Verbindung unterbrochen und Ausgang unklar. Nur tatsächlich verfügbare Hardwarezustände anzeigen; keine erfundene Etikettenrestmenge bei einem Modell ohne solche Rückmeldung.

Updates zunächst bewusst freigegeben und nicht während aktiver Spoolerübergabe. Paketprüfung, Version, Zielarchitektur, Hash/Signatur und Rückfallmöglichkeit vor Installation. Journal und Gerätekonfiguration über Update erhalten. Backend-API-/Agentenkompatibilität und Mindestfassungen transparent verwalten; keine willkürliche Sperre ohne Betriebskonzept.

## D09 Abnahmematrix

Jede freigegebene Kombination dokumentiert Modell, Windows, Treiber, SDK/Adapter, CPU-Architektur, Anschluss, Rolle/Format, Agentenfassung und Testergebnis.

Pflichtfälle: einzelnes Etikett, mehrere Exemplare, lange Texte, Umlaute, Barcode-Scan, leere Rolle soweit prüfbar, Drucker ausgeschaltet, USB entfernt, Windows-Neustart, Agentenabsturz vor/nach Spoolerübergabe, erneute Jobabholung, zwei Agenten, falsche Station, widerrufener Agent, umbenannter Drucker, veraltete Vorlage, Update mit offenen Jobs und bewusst freigegebener Nachdruck.

Ein virtueller Drucker und Adapter-Mocks prüfen Softwarepfade, aber nicht die physische DYMO-Unterstützung. Die erste Warenstrecke benötigt mindestens einen real getesteten Drucker; weitere Modelle werden einzeln ergänzt. Belegdrucker/ESC-POS und nicht-DYMO-Geräte bleiben mögliche spätere Adapter, keine zugesagte fertige Funktion dieses ersten DYMO-Auftrags.

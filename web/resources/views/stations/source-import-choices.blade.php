<label class="check"><input type="checkbox" name="import_hours" value="1" @checked($draft['imports']['hours'] ?? false)>Geprüfte Öffnungszeiten übernehmen – nur bisher unbelegte Wochentage.</label>
<label class="check"><input type="checkbox" name="import_coordinates" value="1" @checked($draft['imports']['coordinates'] ?? false)>Geprüfte Koordinaten übernehmen – nur wenn beide Koordinatenfelder leer sind.</label>
<p class="field-help">Mehrdeutige Zeiten und Feiertagsangaben werden nicht übernommen. Bereits eingetragene Werte bleiben erhalten.</p>

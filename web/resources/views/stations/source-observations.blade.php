<details @if($expanded ?? false) open @endif><summary>Öffnungszeiten und Koordinaten aus der Quelle prüfen</summary>
<dl class="station-summary">@foreach(\App\Stations\StationFields::DAYS as $day => $label)
@php $hours = $source['data']['opening_hours'][$day] ?? []; @endphp
<div><dt>{{ $label }}</dt><dd>{{ match($hours['status'] ?? '') { 'all_day' => '24 Stunden geöffnet', 'closed' => 'Geschlossen', 'interval' => ($hours['open'] ?? '').'–'.($hours['close'] ?? '').(($hours['close'] ?? '') < ($hours['open'] ?? '') ? ' (bis Folgetag)' : ''), default => 'Nicht eindeutig angegeben' } }}</dd></div>
@endforeach</dl>
<p>Koordinaten: {{ isset($source['data']['coordinates']) ? $source['data']['coordinates']['latitude'].' / '.$source['data']['coordinates']['longitude'] : 'Nicht eindeutig verfügbar' }}</p>
@foreach($source['data']['hours_notes'] ?? [] as $note)<p>{{ $note }}</p>@endforeach
<p>Quellenangaben sind Vorschläge. Erst Ihre Auswahl und Bestätigung ergänzt freie Stammdatenfelder.</p>
</details>

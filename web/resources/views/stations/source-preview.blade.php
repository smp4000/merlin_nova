{{-- Öffentliche Quellpreise sind eine separate Momentaufnahme, keine manuell gepflegten Stammdaten. --}}
<section class="provider-preview" aria-label="Benzinpreisdaten">
    <h3>Benzinpreisdaten</h3>
    @include('stations.source-observations')
    <p>{{ $source['data']['name'] }} · {{ $source['data']['street'] }} · {{ $source['data']['postal_code'] }} {{ $source['data']['city'] }}</p>
    <dl class="station-summary">
    @foreach(['e5' => 'Super E5', 'e10' => 'Super E10', 'diesel' => 'Diesel'] as $fuel => $label)
        <div><dt>{{ $label }}</dt><dd>{{ isset($source['data']['prices'][$fuel]) ? str_replace('.', ',', $source['data']['prices'][$fuel]).' €/l' : 'Nicht verfügbar' }}
        @if($source['data']['prices_last_known'][$fuel] ?? false)
            <small>Zuletzt bekannt ·
            @if($source['data']['prices_observed_at'][$fuel] ?? null)
                {{ \Carbon\Carbon::parse($source['data']['prices_observed_at'][$fuel])->timezone('Europe/Berlin')->format('d.m.Y H:i') }} Uhr
            @else
                Preiszeitpunkt nicht angegeben
            @endif
            · kein bestätigter aktueller Verkaufspreis</small>
        @endif
        </dd></div>
    @endforeach
    </dl>
    @if(in_array(true, $source['data']['prices_last_known'] ?? [], true))<p class="field-help">Zuletzt bekannte Preise bleiben auch bei geschlossener Station erhalten. Sie sind kein bestätigter aktueller Verkaufspreis. Maßgeblich ist die Anzeige vor Ort.</p>@endif
    <p><small>Quelle: <a href="{{ $source['source_url'] }}" target="_blank" rel="noopener noreferrer">Benzinpreis-Aktuell.de</a> · Abgerufen am {{ \Carbon\Carbon::parse($source['fetched_at'])->timezone('Europe/Berlin')->format('d.m.Y H:i') }} Uhr. @if(\Carbon\Carbon::parse($source['fetched_at'])->lt(now()->subMinutes(5))) Veralteter Abrufstand. @endif Preise können sich inzwischen geändert haben; ein eigener Preisänderungszeitpunkt liegt nicht vor.</small></p>
</section>

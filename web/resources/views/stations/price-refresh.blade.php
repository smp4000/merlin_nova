@php
    $priceRun = session('price_refresh_runs.'.$station->partner_id.'_'.$station->id);
    if (($priceRun['expires_at'] ?? 0) <= now()->timestamp) $priceRun = null;
    $pricePending = $priceRun && $priceRun['done'] < count($priceRun['jobs']);
@endphp
@if($station->external_source_id || $sourceCompetitors->count())
<section id="price-refresh" class="station-price-refresh" aria-label="Gemeinsame Benzinpreisaktualisierung">
    @if($canWrite && (!$pricePending || $priceRun['paused']))
    <form method="post" action="{{ route('stations.prices.refresh-all', [$partner->id, $station->id]) }}">@csrf
        <input type="hidden" name="action" value="start"><button class="button primary">Alle Benzinpreise aktualisieren</button>
    </form>
    @endif
    <div>
    @if($priceRun)
        <p role="status"><strong>{{ $priceRun['done'] }} von {{ count($priceRun['jobs']) }} Preisständen aktualisiert.</strong>
        @if(!$pricePending) Fertig.
        @elseif($priceRun['paused']) Angehalten.
        @else Als Nächstes: {{ $priceRun['jobs'][$priceRun['done']]['name'] }}.
        @endif
        </p>
    @endif
    <p class="field-help">Eigene Anbindung und gespeicherte Quell-Wettbewerber nacheinander. Manuelle Preise bleiben erhalten.</p>
    </div>
    @if($pricePending && $canWrite)
    <form method="post" action="{{ route('stations.prices.refresh-all', [$partner->id, $station->id]) }}" data-price-refresh @if(!$priceRun['paused'] && !$errors->any()) data-continue @endif>@csrf
        <input type="hidden" name="action" value="next"><input type="hidden" name="run_token" value="{{ $priceRun['token'] }}">
        <button class="button secondary">{{ $priceRun['paused'] ? 'Aktualisierung fortsetzen' : 'Nächsten Preisstand aktualisieren' }}</button>
        @if(!$priceRun['paused'])<button class="button secondary" name="action" value="pause">Anhalten</button>@endif
    </form>
    @endif
    @if(isset($priceRun['error']))<p class="field-help" role="alert">{{ $priceRun['error'] }} Bereits aktualisierte Preisstände bleiben gespeichert. Bitte später bewusst fortsetzen oder neu starten.</p>@endif
</section>
@once<script src="{{ asset('js/nova-price-refresh.js') }}" defer></script>@endonce
@endif

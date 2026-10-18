<x-filament-panels::page>
    <style>
        .nova-billing{display:grid;grid-template-columns:minmax(300px,1fr) minmax(500px,2fr);gap:24px;align-items:start}.nova-billing label{display:block;font-weight:600;margin:16px 0 6px}.nova-billing input:not([type=checkbox]),.nova-billing select,.nova-billing textarea{width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;color:#172b3a;font:inherit}.nova-billing button{padding:10px 16px;border-radius:8px;border:1px solid #cbd5e1;background:#fff;cursor:pointer;margin-top:16px}.nova-billing button.primary{background:#1266c4;border-color:#1266c4;color:#fff}.nova-billing .fields{display:grid;grid-template-columns:1fr 1fr;gap:16px}.nova-billing .hint{color:#526575;font-size:14px;line-height:1.6;margin:12px 0}.nova-billing table{width:100%;border-collapse:collapse;text-align:left}.nova-billing th,.nova-billing td{padding:12px 8px;border-bottom:1px solid #e2e8f0}.nova-billing :focus-visible{outline:3px solid #80bfff;outline-offset:2px}@media(max-width:1100px){.nova-billing{grid-template-columns:1fr}}@media(max-width:600px){.nova-billing .fields{grid-template-columns:1fr}}
    </style>
    <p>Plattformweite Vorgaben für neue Angebote. Hier werden keine Abos abgeschlossen, Rechnungen gestellt oder Lastschriften ausgelöst.</p>
    @if ($notice)<div role="status">{{ $notice }}</div>@endif
    @if ($errors->any())<div role="alert"><strong>Bitte prüfen:</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="nova-billing">
        <div>
            <x-filament::section heading="Kostenloser Test">
                <p class="hint">Gilt erst für künftig beginnende Tests. Ohne gespeicherte Konfiguration ist noch kein Standard freigegeben.</p>
                <form wire:submit="saveTrialPolicy">
                    <label for="trial-days">Testdauer in Tagen</label><input id="trial-days" type="number" min="1" max="3650" required wire:model="trialDays">
                    <label for="trial-stations">Stationen im Test</label><input id="trial-stations" type="number" min="1" max="10000" required wire:model="trialStationLimit">
                    <button class="primary" type="submit" wire:loading.attr="disabled">Neue Testregeln aktivieren</button>
                </form>
                <p class="hint">Feste Produktregeln: Start nach E-Mail-Bestätigung; keine automatische Kostenpflicht; nach Ablauf ohne Abschluss nur lesend. Die Partnerabläufe werden im nächsten Fachpaket implementiert.</p>
                <h3>Letzte fünf Fassungen</h3>
                <ul>@forelse ($trialHistory as $policy)<li>#{{ $policy->id }} · {{ $policy->duration_days }} Tage · {{ $policy->station_limit }} Station(en){{ $policy->id === $expectedPolicyId ? ' · aktueller geladener Stand' : '' }}</li>@empty<li>Noch keine Testregeln gespeichert.</li>@endforelse</ul>
            </x-filament::section>
        </div>
        <div>
            <x-filament::section heading="Tarifkatalog">
                <label for="tariff-choice">Tarif auswählen oder neu anlegen</label>
                <select id="tariff-choice" wire:change="chooseTariff($event.target.value)"><option value="">Neuer Tarif</option>@foreach ($tariffs as $tariff)<option value="{{ $tariff->id }}" @selected($selectedTariffId === $tariff->id)>{{ $tariff->code }}</option>@endforeach</select>
                <form wire:submit="saveDraft">
                    <div class="fields"><div><label for="tariff-code">Stabiler Tarifcode</label><input id="tariff-code" wire:model="code" required maxlength="64" @disabled($selectedTariffId !== null) placeholder="z. B. nova-basis"></div><div><label for="tariff-name">Anzeigename</label><input id="tariff-name" wire:model="name" required maxlength="255"></div></div>
                    <label for="tariff-description">Beschreibung / Leistungsumfang</label><textarea id="tariff-description" wire:model="description" rows="3" maxlength="5000"></textarea>
                    <div class="fields"><div><label for="tariff-price">Nettopreis in EUR je Periode</label><input id="tariff-price" wire:model="price" inputmode="decimal" required placeholder="Preis frei eingeben"></div><div><label for="tariff-interval">Abrechnungsperiode</label><select id="tariff-interval" wire:model="billingInterval"><option value="monthly">Monatlich</option><option value="yearly">Jährlich</option></select></div></div>
                    <div class="fields"><div><label for="tariff-basis">Preis gilt pro</label><select id="tariff-basis" wire:model="billingBasis"><option value="partner">Partnerfirma</option><option value="station">Station</option></select></div><div><label for="tariff-limit">Stationslimit (leer = unbegrenzt)</label><input id="tariff-limit" type="number" min="1" max="10000" wire:model="stationLimit"></div></div>
                    <p class="hint">Jedes Speichern erzeugt eine neue unveränderliche Entwurfsfassung. Keine rückwirkenden Preisänderungen. Steuerberechnung, Module und Vertragslaufzeiten folgen mit der Abrechnung.</p>
                    <button class="primary" type="submit" wire:loading.attr="disabled">Neue Entwurfsfassung speichern</button>
                </form>
            </x-filament::section>
            @if ($selectedTariffId)
            <x-filament::section heading="Fassungen und Freigabe – letzte zehn Versionen">
                <label><input type="checkbox" wire:model="confirmPublication"> Ich möchte die gewählte Fassung ausdrücklich veröffentlichen.</label>
                <div style="overflow-x:auto"><table><thead><tr><th>Fassung</th><th>Preis</th><th>Status</th><th>Aktion</th></tr></thead><tbody>
                @foreach ($versions as $version)
                    <tr><td>#{{ $version->number }} · {{ $version->name }}</td><td>{{ intdiv($version->price_cents, 100) }},{{ str_pad((string) ($version->price_cents % 100), 2, '0', STR_PAD_LEFT) }} EUR / {{ $version->billing_interval === 'monthly' ? 'Monat' : 'Jahr' }}<br>pro {{ $version->billing_basis === 'partner' ? 'Firma' : 'Station' }}</td><td>{{ $expectedVersionId === $version->id ? 'Veröffentlicht · aktuell' : ($version->publication ? 'Früher veröffentlicht' : 'Entwurf') }}</td><td>@if (!$version->publication)<button type="button" wire:click="publishVersion({{ $version->id }})" wire:loading.attr="disabled">Veröffentlichen</button>@endif</td></tr>
                @endforeach
                </tbody></table></div>
            </x-filament::section>
            @endif
        </div>
    </div>
</x-filament-panels::page>

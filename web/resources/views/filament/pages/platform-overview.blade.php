<x-filament-panels::page>
    <x-filament::section heading="Merlin Nova – Plattformverwaltung">
        <p>Hier verwalten Sie künftig Partner, Tarife und Abrechnung. Aktuell ist die sichere Zugangsgrundlage eingerichtet.</p>
        <p>Plattformrechte öffnen keine Personalakten und ersetzen keine Partnerberechtigung.</p>
    </x-filament::section>
    <x-filament::section heading="Partner – maximal 50 Einträge">
        <table style="width:100%;border-collapse:collapse;text-align:left">
            <thead><tr><th style="padding:12px">Partner</th><th style="padding:12px">Zugangsstatus</th></tr></thead>
            <tbody>
            @forelse ($partners as $partner)
                <tr style="border-top:1px solid #e2e8f0"><td style="padding:12px">{{ $partner->name }}</td><td style="padding:12px">{{ $partner->is_active ? 'Aktiv' : 'Gesperrt' }}</td></tr>
            @empty
                <tr><td colspan="2" style="padding:12px">Noch keine Partner angelegt. Die Registrierung folgt im nächsten Fachpaket.</td></tr>
            @endforelse
            </tbody>
        </table>
    </x-filament::section>
</x-filament-panels::page>

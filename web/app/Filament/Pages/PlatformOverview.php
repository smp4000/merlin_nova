<?php

namespace App\Filament\Pages;

use App\Models\Partner;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard;

/**
 * Kleine, eigene Plattformübersicht statt eines ungeprüften CRUD-Generators.
 *
 * Aktuell werden ausschließlich Firmennamen und der technische Aktivstatus
 * angezeigt. Ein Superadmin kann dadurch Plattformpartner überblicken, erhält
 * aber keine Mitgliedschaft und keinen Durchgriff auf operative Partnerdaten.
 * Die Tarif-/Testverwaltung ist eine eigene Seite. Supportfreigaben und
 * tatsächliche Abrechnung bleiben separate, noch offene Fachpakete.
 */
class PlatformOverview extends Dashboard
{
    protected static ?string $title = 'Plattformübersicht';

    protected string $view = 'filament.pages.platform-overview';

    public static function canAccess(): bool
    {
        $panel = Filament::getCurrentOrDefaultPanel();

        return auth()->user()?->canAccessPanel($panel) ?? false;
    }

    protected function getViewData(): array
    {
        abort_unless(static::canAccess(), 403);

        return ['partners' => Partner::query()->select(['id', 'name', 'is_active'])->orderBy('name')->limit(50)->get()];
    }
}

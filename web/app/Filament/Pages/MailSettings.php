<?php

namespace App\Filament\Pages;

use App\Models\PlatformMailSetting;
use Filament\Pages\Page;

/**
 * Plattformweite Mailseite im bestehenden Panel. Das Passwort ist bewusst kein
 * Livewire-Zustand: Ein normales CSRF-Formular sendet es einmal an den Controller.
 * Ansichten erhalten nur freigegebene Metadaten, niemals den Model-Geheimwert.
 */
class MailSettings extends Page
{
    protected static ?string $title = 'E-Mail-Versand';

    protected static ?string $slug = 'email';

    protected string $view = 'filament.pages.mail-settings';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('manage-mail') ?? false;
    }

    protected function getViewData(): array
    {
        abort_unless(static::canAccess(), 403);
        $settings = PlatformMailSetting::find(1);

        return ['settings' => [
            'username' => $settings?->username ?? '', 'from_address' => $settings?->from_address ?? '',
            'from_name' => $settings?->from_name ?? 'Merlin Nova', 'enabled' => $settings?->enabled ?? false,
            'has_password' => (bool) $settings?->password,
            'port' => $settings?->port ?? 465,
        ]];
    }
}

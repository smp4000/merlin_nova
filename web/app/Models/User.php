<?php

namespace App\Models;

use App\Enums\PartnerRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Persönlicher Zugang zur Anwendung, nicht die spätere Mitarbeiterakte.
 *
 * Ein Benutzer kann mehrere Partner-Mitgliedschaften mit unterschiedlichen
 * Rollen besitzen. Plattformrechte sind davon unabhängig. Sicherheitsfelder
 * stehen absichtlich nicht in Fillable: Formulardaten dürfen weder den
 * Superadminstatus noch die E-Mail-Bestätigung oder Kontosperre verändern.
 * Ein Superadmin erhält insbesondere keinen globalen Policy-Bypass.
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    protected $attributes = ['is_active' => true, 'is_super_admin' => false];

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Sicherheitsflags als echte boolesche Werte behandeln und Passwörter
     * ausschließlich über Laravels konfigurierten Hashalgorithmus speichern.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Die einzige Eintrittsbedingung für das Plattformpanel. Filament fragt
     * diese Methode auch in der lokalen Umgebung ab, weil FilamentUser
     * ausdrücklich implementiert ist. Eine Partnerrolle genügt niemals.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'platform' && $this->is_active && $this->is_super_admin && $this->hasVerifiedEmail();
    }

    /**
     * Prüft die konkrete aktive Mitgliedschaft bei jedem Aufruf neu.
     *
     * Kein Rollenwert aus Formular, URL oder Session und kein Rollen-Cache:
     * ein Entzug der Mitgliedschaft soll beim nächsten Zugriff greifen.
     * Unbestätigte/gesperrte Konten und gesperrte Partner bekommen keine Rolle.
     * Die Plattform-Superadminrolle wird hier bewusst nicht berücksichtigt.
     */
    public function roleFor(Partner $partner): ?PartnerRole
    {
        if (! $this->is_active || ! $this->hasVerifiedEmail() || ! $partner->is_active) {
            return null;
        }

        return PartnerMembership::query()
            ->where('user_id', $this->getKey())
            ->where('partner_id', $partner->getKey())
            ->where('is_active', true)
            ->first()?->role;
    }
}

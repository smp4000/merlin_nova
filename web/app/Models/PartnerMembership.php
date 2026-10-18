<?php

namespace App\Models;

use App\Enums\PartnerRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ausdrückliche Zuordnung eines Benutzerkontos zu genau einer Partnerfirma.
 *
 * Pro Benutzer/Partner gibt es dank Datenbank-Unique-Constraint nur einen
 * Eintrag. Die Rolle gilt ausschließlich in dieser Firma, nicht in anderen
 * Mitgliedschaften. Alle Vergabefelder sind gegen Mass Assignment gesperrt;
 * spätere Verwaltungsaktionen müssen Berechtigung und erlaubte Rolle prüfen.
 * Diese Sicherheits-Zuordnung ist kein Mitarbeitervertrag und noch keine
 * Stationsberechtigung. Sie wird vor Aufbau des Partnerkontexts abgefragt
 * und besitzt deshalb selbst keinen PartnerOwnedModel-Global-Scope.
 */
class PartnerMembership extends Model
{
    protected $attributes = ['is_active' => true];

    protected $guarded = ['id', 'partner_id', 'user_id', 'role', 'is_active'];

    protected function casts(): array
    {
        return ['role' => PartnerRole::class, 'is_active' => 'boolean'];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Stabile Identität eines Plattformtarifs. Preise stehen nie in diesem Kopf,
 * sondern in unveränderlichen TariffVersion-Datensätzen. Nur der bewusste
 * Veröffentlichungsprozess darf den Zeiger auf die aktuelle Fassung ändern.
 * Kein Partner-Scope: Zugriff ist ausschließlich Plattformberechtigten erlaubt.
 * Der Service sperrt diesen Kopf während Versionsvergabe/Veröffentlichung.
 */
class Tariff extends Model
{
    protected $guarded = ['*'];

    public function versions(): HasMany
    {
        return $this->hasMany(TariffVersion::class);
    }

    public function publishedVersion(): BelongsTo
    {
        return $this->belongsTo(TariffVersion::class, 'published_version_id');
    }
}

<?php

namespace App\Models;

use App\Tenancy\PartnerContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Sicherheitsbasis für operative Eloquent-Modelle mit partner_id.
 *
 * Abfragen ohne ausdrücklich freigegebenen Kontext werden abgewiesen statt
 * versehentlich alle Mandanten zu laden. Der Scope begrenzt auch Route Model
 * Binding. Modellereignisse schützen gespeicherte Objekte zusätzlich, wenn
 * sie vor einem Kontextwechsel geladen wurden oder ihre Zuordnung verändert
 * wird. Das ist keine datenbankseitige Row-Level-Security: DB::table,
 * withoutGlobalScopes und Bulk-Updates umgehen Eloquent-Modellereignisse.
 * Solche Wege sind in operativen Request-/Jobpfaden nicht zulässig; neue
 * Module brauchen jeweils Policies, Eingabe-Whitelists und negative Tests.
 */
abstract class PartnerOwnedModel extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope('partner', function (Builder $query): void {
            $query->where($query->getModel()->qualifyColumn('partner_id'), app(PartnerContext::class)->id());
        });

        static::saving(function (self $record): void {
            $partnerId = app(PartnerContext::class)->id();
            if ($record->exists && (int) $record->getOriginal('partner_id') !== $partnerId) {
                throw new AuthorizationException('Der Datensatz gehört nicht zum aktiven Partner.');
            }
            if ($record->partner_id !== null && (int) $record->partner_id !== $partnerId) {
                throw new AuthorizationException('Der Partner eines Datensatzes darf nicht geändert werden.');
            }
            $record->partner_id = $partnerId;
        });

        static::deleting(function (self $record): void {
            if ((int) $record->getOriginal('partner_id') !== app(PartnerContext::class)->id()) {
                throw new AuthorizationException('Der Datensatz gehört nicht zum aktiven Partner.');
            }
        });
    }
}

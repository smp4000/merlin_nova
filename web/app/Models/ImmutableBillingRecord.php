<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * Append-only-Basis für zugesagte Preis-/Testfassungen und Veröffentlichungen.
 * Änderungen erfolgen als neue Datensätze, damit spätere Verträge auf ihre
 * ursprüngliche Fassung verweisen können. Kein Schutz gegen Datenbankadmins
 * oder rohe SQL-/Bulk-Updates: Fachzugriffe laufen nur über BillingCatalog.
 * Geschützte Attribute verhindern ungeprüfte Massenzuweisung aus Formularen.
 */
abstract class ImmutableBillingRecord extends Model
{
    protected $guarded = ['*'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Billing-Fassungen dürfen nicht überschrieben werden.'));
        static::deleting(fn () => throw new LogicException('Billing-Fassungen dürfen nicht gelöscht werden.'));
    }
}

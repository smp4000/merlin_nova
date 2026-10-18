<?php

namespace App\Tenancy;

use App\Models\Partner;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Kurzlebiger, serverseitig autorisierter Partnerkontext eines Requests.
 *
 * Als scoped Service registriert, nicht als statischer globaler Zustand.
 * ResolvePartner setzt ihn erst nach Authentifizierung und Mitgliedschafts-
 * prüfung und leert ihn selbst bei Fehlern im finally-Block. Das verhindert
 * das Weiterreichen eines Partners an nachfolgende Requests eines Workers.
 * set() ist eine interne Vertrauensgrenze, kein öffentlicher Partnerwechsel.
 * Hintergrundjobs müssen künftig ihre eigene Mitgliedschaft/Freigabe prüfen,
 * bevor sie einen Kontext öffnen; fehlender Kontext führt bewusst zum Fehler.
 */
final class PartnerContext
{
    private ?Partner $partner = null;

    public function set(Partner $partner): void
    {
        $this->partner = $partner;
    }

    public function id(): int
    {
        if (! $this->partner) {
            throw new AuthorizationException('Kein freigegebener Partnerkontext vorhanden.');
        }

        return (int) $this->partner->getKey();
    }

    public function clear(): void
    {
        $this->partner = null;
    }
}

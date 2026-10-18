<?php

namespace App\Models;

/**
 * Unveränderlicher Nachweis, wer eine konkrete Tarifversion freigegeben hat.
 * Der eindeutige Versions-Fremdschlüssel verhindert doppelte Veröffentlichungen.
 * Ältere Nachweise bleiben bestehen, wenn später eine neue Fassung aktiv wird.
 * created_by darf bei späterer rechtmäßiger Kontolöschung anonymisiert werden;
 * dies ist kein vollständiges manipulationssicheres Sicherheits-Auditprotokoll.
 */
class TariffPublication extends ImmutableBillingRecord {}

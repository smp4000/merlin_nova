<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Absichtlich keine vorgegebenen Benutzer oder bekannten Standardpasswörter.
 * Lokale Superadmins werden nur über nova:superadmin mit eigener verdeckter
 * Passworteingabe angelegt. Test-Factories gehören ausschließlich in Tests.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Keine stillschweigende Konten- oder Rechtevergabe bei db:seed.
     */
    public function run(): void
    {
        // Fachliche Beispieldaten erhalten später einen gesonderten Auftrag.
    }
}

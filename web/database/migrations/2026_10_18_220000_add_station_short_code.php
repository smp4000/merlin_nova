<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bestehende Nummern werden bewusst weder kopiert noch umgedeutet.
        // NULL erlaubt die schrittweise Ergänzung alter Stationen; neue Anlagen
        // erhalten ihren Code unter Partnersperre in der Anwendungslogik.
        Schema::table('stations', function (Blueprint $table) {
            $table->string('short_code', 8)->nullable();
            $table->unique(['partner_id', 'short_code']);
        });
    }

    public function down(): void
    {
        Schema::table('stations', function (Blueprint $table) {
            $table->dropUnique(['partner_id', 'short_code']);
            $table->dropColumn('short_code');
        });
    }
};

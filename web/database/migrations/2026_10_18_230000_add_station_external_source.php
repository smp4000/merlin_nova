<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Quellenverknüpfung getrennt von manuellen Stammdaten, Kurzcode und Gesellschaftsnummer. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stations', function (Blueprint $table) {
            $table->string('external_source_id', 160)->nullable();
            $table->json('external_source_data')->nullable();
            $table->unique(['partner_id', 'external_source_id']);
        });
    }

    public function down(): void
    {
        Schema::table('stations', function (Blueprint $table) {
            $table->dropUnique(['partner_id', 'external_source_id']);
            $table->dropColumn(['external_source_id', 'external_source_data']);
        });
    }
};

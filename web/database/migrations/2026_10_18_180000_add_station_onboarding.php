<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Ergänzt manuelle Stationsdaten; alte technische Stationen bleiben unverändert lesbar. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stations', function (Blueprint $table) {
            $table->string('brand', 100)->nullable();
            $table->string('street')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('city', 100)->nullable();
            $table->char('country_code', 2)->nullable();
            $table->uuid('creation_token')->nullable();
            $table->char('identity_hash', 64)->nullable();
            $table->unique(['partner_id', 'creation_token']);
            $table->unique(['partner_id', 'identity_hash']);
        });
    }

    public function down(): void
    {
        Schema::table('stations', function (Blueprint $table) {
            $table->dropUnique(['partner_id', 'creation_token']);
            $table->dropUnique(['partner_id', 'identity_hash']);
            $table->dropColumn(['brand', 'street', 'postal_code', 'city', 'country_code', 'creation_token', 'identity_hash']);
        });
    }
};

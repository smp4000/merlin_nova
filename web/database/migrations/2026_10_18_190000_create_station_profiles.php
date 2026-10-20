<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Erweiterte Stationsstammdaten bleiben vom schmalen Stationslistenmodell getrennt.
 * Der zusammengesetzte Fremdschlüssel erzwingt die gleiche Partnerzuordnung auch
 * in MySQL. Verschlüsselte Nutzdaten werden nur über den geprüften Fachservice
 * gelesen/geschrieben; bestehende Stationen benötigen keine erfundenen Vorbelegungen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stations', function (Blueprint $table) {
            $table->string('house_number', 20)->nullable();
            $table->unique(['partner_id', 'id']);
        });
        Schema::create('station_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('station_id');
            $table->longText('data');
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('updated_by')->constrained('users')->restrictOnDelete();
            $table->unique(['partner_id', 'station_id']);
            $table->foreign(['partner_id', 'station_id'])->references(['partner_id', 'id'])->on('stations')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('station_profiles');
        Schema::table('stations', function (Blueprint $table) {
            $table->dropUnique(['partner_id', 'id']);
            $table->dropColumn('house_number');
        });
    }
};

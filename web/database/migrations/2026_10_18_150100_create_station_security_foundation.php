<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Minimaler operativer Datensatz für echte Mandantentrennungs-Tests.
 * Der Fremdschlüssel verhindert verwaiste Stationen; der Eloquent-Scope
 * übernimmt die Zugriffstrennung. Ein vollständiges Stationsschema wird
 * später durch zusätzliche Migrationen ergänzt, nicht hier rückwirkend.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stations');
    }
};

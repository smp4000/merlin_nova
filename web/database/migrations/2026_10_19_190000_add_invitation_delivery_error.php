<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Nur feste Versandkategorien speichern; historische Fehler bleiben unbekannt. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_invitations', function (Blueprint $table): void {
            $table->string('failure_code', 24)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('employee_invitations', function (Blueprint $table): void {
            $table->dropColumn('failure_code');
        });
    }
};

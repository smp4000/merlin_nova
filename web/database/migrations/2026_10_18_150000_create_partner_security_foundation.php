<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trennt persönliche Zugänge, Firmen und deren ausdrückliche Mitgliedschaften.
 * Die eindeutige Benutzer/Firmen-Kombination verhindert widersprüchliche
 * parallele Rolleneinträge. Plattformrechte gehören zum Zugang, nicht zur
 * Firma; is_active sperrt technisch und bildet keinen Abo-Lebenszyklus ab.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_super_admin')->default(false);
            $table->boolean('is_active')->default(true);
        });

        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('partner_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['owner', 'manager', 'staff', 'viewer']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['partner_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_memberships');
        Schema::dropIfExists('partners');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_super_admin', 'is_active']);
        });
    }
};

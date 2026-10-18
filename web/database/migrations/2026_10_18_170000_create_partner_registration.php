<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registrierung, zugesagte Testbedingungen und vorbereitendes Mandat trennen.
 * Der gehashte Formularschlüssel verhindert doppelte Konten bei Wiederholung.
 * Bank-/Adressfelder sind TEXT für Laravels verschlüsselte Modellattribute.
 * Testdaten bleiben bis zur erfolgreichen E-Mail-Bestätigung ohne Startdatum.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('registration_token_hash', 64)->nullable()->unique();
            $table->string('registration_payload_hash', 64)->nullable();
        });
        Schema::table('partners', function (Blueprint $table) {
            $table->foreignId('registration_owner_id')->nullable()->unique()->constrained('users')->restrictOnDelete();
            $table->foreignId('trial_policy_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedInteger('trial_duration_days')->nullable();
            $table->unsignedInteger('trial_station_limit')->nullable();
            $table->timestamp('trial_started_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
        });
        Schema::create('sepa_mandates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->unique()->constrained()->restrictOnDelete();
            $table->string('reference', 35)->unique();
            $table->text('iban');
            $table->text('account_holder');
            $table->text('debtor_address');
            $table->string('status')->default('prepared');
            $table->string('declaration_version');
            $table->text('declaration_text');
            $table->timestamp('declared_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sepa_mandates');
        Schema::table('partners', function (Blueprint $table) {
            $table->dropConstrainedForeignId('registration_owner_id');
            $table->dropConstrainedForeignId('trial_policy_id');
            $table->dropColumn(['trial_duration_days', 'trial_station_limit', 'trial_started_at', 'trial_ends_at']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['registration_token_hash']);
            $table->dropColumn(['registration_token_hash', 'registration_payload_hash']);
        });
    }
};

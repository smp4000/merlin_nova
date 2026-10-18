<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Plattformweiter Katalog, nicht operative Daten eines einzelnen Partners.
 * Fassungen und Veröffentlichungen bleiben als Historie erhalten. Es werden
 * bewusst weder Beispielpreise noch eine stillschweigende Testdauer aktiviert.
 * Ein Singleton dient als Sperrpunkt für konkurrierende Testregeländerungen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tariffs', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->unsignedBigInteger('published_version_id')->nullable();
            $table->timestamps();
        });
        Schema::create('tariff_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tariff_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('number');
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('price_cents');
            $table->enum('billing_interval', ['monthly', 'yearly']);
            $table->enum('billing_basis', ['partner', 'station']);
            $table->unsignedInteger('station_limit')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['tariff_id', 'number']);
        });
        Schema::table('tariffs', function (Blueprint $table) {
            $table->foreign('published_version_id')->references('id')->on('tariff_versions')->restrictOnDelete();
        });
        Schema::create('tariff_publications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tariff_version_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('trial_policies', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('duration_days');
            $table->unsignedInteger('station_limit');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('billing_controls', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->foreignId('trial_policy_id')->nullable()->constrained()->restrictOnDelete();
        });
        DB::table('billing_controls')->insert(['id' => 1]);
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_controls');
        Schema::dropIfExists('trial_policies');
        Schema::dropIfExists('tariff_publications');
        Schema::table('tariffs', fn (Blueprint $table) => $table->dropForeign(['published_version_id']));
        Schema::dropIfExists('tariff_versions');
        Schema::dropIfExists('tariffs');
    }
};

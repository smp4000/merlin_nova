<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Eigenständige Mitarbeiterakten im Nova-Partner. Personalinhalte sind verschlüsselte
 * Fassungen; Konto-/Stationsrechte entstehen dadurch nicht. Zusammengesetzte
 * Fremdschlüssel verhindern auch außerhalb Eloquent eine mandantenfremde Zuordnung.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('partner_id')->constrained()->restrictOnDelete();
            $table->foreignId('station_id')->constrained()->restrictOnDelete();
            $table->foreign(['partner_id', 'station_id'])->references(['partner_id', 'id'])->on('stations')->restrictOnDelete();
            $table->string('profile', 20);
            $table->string('status', 24)->default('draft');
            $table->text('identity');
            $table->longText('personal_data')->nullable();
            $table->longText('employer_data')->nullable();
            $table->string('personnel_number', 20)->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->uuid('creation_key');
            $table->timestamps();
            $table->unique(['partner_id', 'id']);
            $table->unique(['partner_id', 'creation_key']);
            $table->unique(['partner_id', 'personnel_number']);
        });
        Schema::create('employee_invitations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('partner_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('employee_id');
            $table->foreign(['partner_id', 'employee_id'])->references(['partner_id', 'id'])->on('employees')->restrictOnDelete();
            $table->string('status', 24)->default('sending');
            $table->char('token_hash', 64)->nullable();
            $table->char('grant_hash', 64)->nullable();
            $table->timestamp('expires_at');
            $table->longText('data')->nullable();
            $table->longText('submitted_data')->nullable();
            $table->text('question')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('invited_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
        Schema::create('employee_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('partner_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('employee_id');
            $table->foreign(['partner_id', 'employee_id'])->references(['partner_id', 'id'])->on('employees')->restrictOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('action', 24);
            $table->unsignedInteger('employee_version');
            $table->longText('snapshot');
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_reviews');
        Schema::dropIfExists('employee_invitations');
        Schema::dropIfExists('employees');
    }
};

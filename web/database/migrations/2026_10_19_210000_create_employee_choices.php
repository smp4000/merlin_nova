<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Explizit gepflegte Partner-Ergänzungen, ohne bestehende Personalwerte zu verändern. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_choices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('partner_id')->constrained()->restrictOnDelete();
            $table->string('category', 24);
            $table->string('label', 160);
            $table->char('identity_hash', 64);
            $table->timestamps();
            $table->unique(['partner_id', 'category', 'identity_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_choices');
    }
};

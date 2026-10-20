<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_mail_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('username')->default('');
            $table->string('from_address')->default('');
            $table->string('from_name')->default('Merlin Nova');
            $table->text('password')->nullable();
            $table->boolean('enabled')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_mail_settings');
    }
};

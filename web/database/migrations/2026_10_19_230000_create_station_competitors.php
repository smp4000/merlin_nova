<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('station_competitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained()->restrictOnDelete();
            $table->foreignId('station_id');
            $table->foreign(['partner_id', 'station_id'])->references(['partner_id', 'id'])->on('stations')->restrictOnDelete();
            $table->string('source_id', 160);
            $table->text('snapshot');
            $table->foreignId('updated_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['station_id', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('station_competitors');
    }
};

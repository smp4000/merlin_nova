<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_mail_settings', fn (Blueprint $table) => $table->unsignedSmallInteger('port')->default(465));
    }

    public function down(): void
    {
        Schema::table('platform_mail_settings', fn (Blueprint $table) => $table->dropColumn('port'));
    }
};

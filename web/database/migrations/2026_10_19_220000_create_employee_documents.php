<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('partner_id')->constrained()->restrictOnDelete();
            $table->foreignId('employee_id');
            $table->foreign(['partner_id', 'employee_id'])->references(['partner_id', 'id'])->on('employees')->restrictOnDelete();
            $table->foreignId('invitation_id')->nullable()->constrained('employee_invitations')->restrictOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('storage_path');
            $table->text('original_name');
            $table->string('mime', 40);
            $table->unsignedInteger('bytes');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_documents');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('email')->unique();
            $table->string('phone', 20)->nullable()->index();
            $table->string('password');
            $table->foreignId('college_id')->constrained('colleges')->restrictOnDelete();
            $table->string('designation_bn', 100)->nullable();
            $table->string('designation_en', 100)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->boolean('is_active')->default(false)->index();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->index(['college_id', 'is_active']);
        });

        Schema::create('teacher_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_password_reset_tokens');
        Schema::dropIfExists('teachers');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitor_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('visitor_id', 64)->unique();
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at')->index();
            $table->string('last_path', 500)->nullable();
            $table->unsignedInteger('page_views')->default(0);
            $table->timestamps();
        });

        Schema::create('material_downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_material_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_file_id')->constrained()->cascadeOnDelete();
            $table->string('visitor_id', 64)->nullable()->index();
            $table->string('ip_hash', 64)->nullable();
            $table->timestamp('downloaded_at')->index();

            $table->index(['study_material_id', 'downloaded_at']);
            $table->index(['material_file_id', 'visitor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_downloads');
        Schema::dropIfExists('visitor_sessions');
    }
};

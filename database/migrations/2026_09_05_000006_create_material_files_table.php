<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_material_id')->constrained()->cascadeOnDelete();
            $table->string('disk', 20)->default('local');
            $table->text('path');
            $table->string('original_name');
            $table->string('extension', 10);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->string('checksum', 64)->nullable();
            $table->unsignedSmallInteger('page_count')->nullable();
            $table->string('label_bn', 150)->nullable();
            $table->string('label_en', 150)->nullable();
            $table->integer('sort_order')->default(0);
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamps();

            $table->index(['study_material_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_files');
    }
};

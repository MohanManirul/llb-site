<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->restrictOnDelete();
            $table->foreignId('program_level_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('code', 20)->nullable()->unique();
            $table->string('slug', 150)->unique();
            $table->string('name_bn', 200);
            $table->string('name_en', 200);
            $table->text('description_bn')->nullable();
            $table->text('description_en')->nullable();
            $table->unsignedSmallInteger('marks')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['program_id', 'program_level_id', 'is_active', 'sort_order'], 'subjects_browse_index');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};

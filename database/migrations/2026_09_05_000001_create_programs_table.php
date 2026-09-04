<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('name_bn', 150);
            $table->string('name_en', 150);
            $table->string('short_name_bn', 40)->nullable();
            $table->string('short_name_en', 40)->nullable();
            $table->boolean('has_levels')->default(true);
            $table->string('level_label_bn', 30)->nullable();
            $table->string('level_label_en', 30)->nullable();
            $table->boolean('has_exam_stages')->default(false);
            $table->boolean('has_sessions')->default(true);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};

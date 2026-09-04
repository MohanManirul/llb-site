<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_materials', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->string('slug', 200)->unique();
            $table->string('title_bn');
            $table->string('title_en')->nullable();
            $table->text('description_bn')->nullable();
            $table->text('description_en')->nullable();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_session_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('exam_stage', 20)->nullable();
            $table->smallInteger('exam_year')->nullable();
            $table->string('content_language', 10)->default('bn');
            $table->string('author')->nullable();
            $table->string('publisher')->nullable();
            $table->string('edition', 50)->nullable();
            $table->unsignedSmallInteger('page_count')->nullable();
            $table->text('cover_image')->nullable();
            $table->json('meta')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('download_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'status', 'published_at']);
            $table->index(['subject_id', 'type', 'status']);
            $table->index(['academic_session_id', 'type', 'status']);
            $table->index(['status', 'is_featured', 'published_at']);
            $table->index(['status', 'download_count']);
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_materials');
    }
};

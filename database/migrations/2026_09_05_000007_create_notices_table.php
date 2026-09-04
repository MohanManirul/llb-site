<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notices', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 200)->unique();
            $table->string('title_bn');
            $table->string('title_en')->nullable();
            $table->string('excerpt_bn', 500)->nullable();
            $table->string('excerpt_en', 500)->nullable();
            $table->text('body_bn');
            $table->text('body_en')->nullable();
            $table->string('category', 30)->default('general');
            $table->foreignId('program_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('program_level_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('academic_session_id')->nullable()->constrained()->restrictOnDelete();
            $table->boolean('is_pinned')->default(false)->index();
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('attachment_disk', 20)->nullable();
            $table->text('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->unsignedBigInteger('attachment_size')->nullable();
            $table->unsignedInteger('attachment_download_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'is_pinned', 'published_at']);
            $table->index(['category', 'status']);
            $table->index(['academic_session_id', 'status']);
            $table->index(['program_id', 'status']);
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notices');
    }
};

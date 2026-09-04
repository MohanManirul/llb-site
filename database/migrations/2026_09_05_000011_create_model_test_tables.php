<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('model_tests', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 200)->unique();
            $table->string('title_bn');
            $table->string('title_en')->nullable();
            $table->text('description_bn')->nullable();
            $table->text('description_en')->nullable();
            $table->foreignId('program_id')->constrained()->restrictOnDelete();
            $table->string('exam_stage', 20)->nullable();
            $table->unsignedSmallInteger('duration_minutes');
            $table->decimal('negative_mark', 4, 2)->default(0.25);
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['program_id', 'status', 'published_at']);
            $table->index('deleted_at');
        });

        Schema::create('model_test_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('model_test_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->decimal('marks', 4, 2)->default(1);
            $table->timestamps();

            $table->unique(['model_test_id', 'question_id']);
            $table->index(['model_test_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_test_questions');
        Schema::dropIfExists('model_tests');
    }
};

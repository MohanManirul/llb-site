<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->string('type', 10);
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->string('exam_stage', 20)->nullable();
            $table->smallInteger('exam_year')->nullable();
            $table->text('question_bn');
            $table->text('question_en')->nullable();
            $table->text('explanation_bn')->nullable();
            $table->text('explanation_en')->nullable();
            $table->string('reference')->nullable();
            $table->string('status', 20)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['subject_id', 'type', 'status']);
            $table->index(['type', 'status', 'exam_year']);
            $table->index(['exam_stage', 'exam_year']);
            $table->index('deleted_at');
        });

        Schema::create('question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->text('option_bn');
            $table->text('option_en')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['question_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_options');
        Schema::dropIfExists('questions');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_milestones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();

            $table->unsignedSmallInteger('sequence');
            $table->string('label', 40)->nullable();

            $table->date('period_start');
            $table->date('period_end');

            $table->decimal('target_amount', 14, 2);
            $table->decimal('achieved_amount', 14, 2)->default(0);

            $table->enum('status', ['upcoming', 'on_track', 'at_risk', 'off_track'])
                ->default('upcoming');
            $table->timestamp('evaluated_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['project_id', 'sequence']);
            $table->index(['company_id', 'project_id']);
            $table->index(['project_id', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_milestones');
    }
};

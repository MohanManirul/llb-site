<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_sales', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();

            $table->foreignId('project_milestone_id')->nullable()->constrained()->nullOnDelete();

            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();

            $table->date('sale_date');
            $table->decimal('amount', 14, 2);
            $table->string('reference', 100)->nullable();   // order id / invoice / external ref
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'project_id']);
            $table->index(['company_id', 'employee_id']);
            $table->index(['company_id', 'team_id']);
            $table->index('project_milestone_id');
            $table->index(['project_id', 'sale_date']);

            $table->unique(['project_id', 'reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_sales');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sales_reports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();

            $table->date('week_start');
            $table->date('week_end');
            $table->decimal('total_sales', 12, 2)->default(0);
            $table->unsignedInteger('total_order_quantity')->default(0);
            $table->decimal('total_amount_spent', 12, 2)->default(0);
            $table->text('description')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['project_id', 'week_start']);
            $table->index(['company_id', 'project_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_reports');
    }
};

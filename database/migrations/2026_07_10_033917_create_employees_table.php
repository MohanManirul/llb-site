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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('department_id')
                ->constrained('departments')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('designation_id')
                ->nullable()
                ->constrained('designations')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->text('description')->nullable();

            $table->boolean('is_active')->default(true);
            $table->date('joining_date')->nullable();
            $table->date('resignation_date')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->index('company_id');
            $table->index('department_id');
            $table->index('user_id');

            $table->index('is_active');

            $table->index(['company_id', 'department_id']);
            $table->index(['company_id', 'is_active']);
            $table->index(['department_id', 'is_active']);

            $table->unique(['user_id', 'company_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};

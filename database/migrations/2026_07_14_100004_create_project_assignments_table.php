<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('team_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete()->cascadeOnUpdate();

            $table->timestamp('assigned_at');
            $table->timestamp('unassigned_at')->nullable();
            $table->enum('reason', ['initial', 'manual', 'milestone_failure', 'performance'])->nullable();

            $table->timestamps();

            $table->index(['company_id', 'project_id']);
            $table->index(['employee_id', 'unassigned_at']);
            $table->index(['team_id', 'unassigned_at']);
        });

        // One open assignment per project+employee. MySQL has no partial index,
        // so it gets a generated column that is NULL once the row is closed —
        // NULLs repeat freely under a unique index, giving the same guarantee.
        if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement(
                'ALTER TABLE project_assignments
                 ADD COLUMN open_assignment TINYINT(1)
                 AS (CASE WHEN unassigned_at IS NULL THEN 1 ELSE NULL END) STORED,
                 ADD UNIQUE INDEX project_assignments_open_unique
                 (project_id, employee_id, open_assignment)'
            );

            return;
        }

        DB::statement(
            'CREATE UNIQUE INDEX project_assignments_open_unique
             ON project_assignments (project_id, employee_id) WHERE unassigned_at IS NULL'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('project_assignments');
    }
};

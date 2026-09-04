<?php

use App\Enums\BusinessStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('department_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('client_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('team_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();

            $table->foreignId('assigned_employee_id')->nullable()
                ->constrained('employees')->nullOnDelete()->cascadeOnUpdate();

            $table->string('project_name', 150)->unique();
            $table->string('business_name', 150);
            $table->text('website_url')->nullable();
            $table->text('description')->nullable();

            $table->date('start_date');
            $table->unsignedTinyInteger('contract_months')->default(12);
            $table->unsignedSmallInteger('contract_days')->default(0);
            $table->date('end_date');

            $table->string('contact_person', 100)->nullable();
            $table->string('contact_email', 150)->nullable();
            $table->string('contact_phone', 20)->nullable();

            $table->decimal('package_amount', 12, 2);
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->decimal('amount_due', 12, 2)->default(0);
            $table->date('last_payment_date')->nullable();
            $table->date('next_payment_date')->nullable();

            $table->enum('project_type', ['regular', 'challenge_based'])->default('regular');

            $table->decimal('sales_target', 14, 2)->nullable();
            $table->date('target_start_date')->nullable();
            $table->unsignedTinyInteger('target_months')->nullable();
            $table->unsignedSmallInteger('target_days')->nullable();
            $table->date('target_deadline')->nullable();

            $table->decimal('achieved_sales', 14, 2)->default(0);
            $table->enum('health_status', ['upcoming', 'on_track', 'at_risk', 'off_track'])
                ->default('upcoming');
            $table->timestamp('health_evaluated_at')->nullable();

            $table->enum('business_status', BusinessStatus::values())
                ->default(BusinessStatus::BusinessSetup->value);

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->index(['company_id', 'health_status']);
            $table->index(['company_id', 'business_status']);
            $table->index(['company_id', 'team_id']);
            $table->index(['company_id', 'assigned_employee_id']);

            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->fullText('business_name');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};

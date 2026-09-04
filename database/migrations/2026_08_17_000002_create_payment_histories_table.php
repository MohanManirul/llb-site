<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->references('id')->on('payments')->nullOnDelete();
            $table->string('action', 100)->default('payment_created');
            $table->decimal('changed_amount', 12, 2)->nullable();
            $table->decimal('old_paid_amount', 12, 2)->nullable();
            $table->decimal('new_paid_amount', 12, 2)->nullable();
            $table->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index('project_id');
            $table->index('payment_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_histories');
    }
};

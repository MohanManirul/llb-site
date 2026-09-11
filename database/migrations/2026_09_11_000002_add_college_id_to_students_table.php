<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('college_id')->nullable()->constrained('colleges')->restrictOnDelete();

            $table->index(['college_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['college_id', 'is_active']);
            $table->dropConstrainedForeignId('college_id');
        });
    }
};

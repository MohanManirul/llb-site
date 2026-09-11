<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            $table->foreignId('college_id')->nullable()->constrained('colleges')->restrictOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();

            $table->index(['college_id', 'status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            $table->dropIndex(['college_id', 'status', 'published_at']);
            $table->dropConstrainedForeignId('teacher_id');
            $table->dropConstrainedForeignId('college_id');
        });
    }
};

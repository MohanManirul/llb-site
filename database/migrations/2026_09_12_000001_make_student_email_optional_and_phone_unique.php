<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });

        // Registration collects the mobile instead of the email, and the login
        // looks an account up by it. The column stays nullable so the accounts
        // created before this migration survive; Postgres keeps multiple NULLs
        // out of the unique index.
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['phone']);
            $table->unique('phone');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['phone']);
            $table->index('phone');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
        });
    }
};

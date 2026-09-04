<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PR #73 added `projects.total_amount`, `projects.last_payment_date` and
 * `notifications.dedupe_key` by editing the original create migrations. Those
 * had already run on every existing database, so Laravel skipped them and the
 * columns never appeared, while the code that writes them shipped. Staging
 * broke on `SQLSTATE[42703]` the moment someone recorded a payment.
 *
 * This adds them forward. The guards are the point: a database built from
 * scratch now gets all three from the create migrations, so this has to be a
 * no-op there rather than a duplicate-column error.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (! Schema::hasColumn('projects', 'total_amount')) {
                $table->decimal('total_amount', 12, 2)->nullable();
            }

            if (! Schema::hasColumn('projects', 'last_payment_date')) {
                $table->date('last_payment_date')->nullable();
            }
        });

        Schema::table('notifications', function (Blueprint $table) {
            if (! Schema::hasColumn('notifications', 'dedupe_key')) {
                $table->string('dedupe_key', 191)->nullable();
                $table->unique('dedupe_key');
            }
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            if (Schema::hasColumn('notifications', 'dedupe_key')) {
                $table->dropUnique(['dedupe_key']);
                $table->dropColumn('dedupe_key');
            }
        });

        Schema::table('projects', function (Blueprint $table) {
            foreach (['total_amount', 'last_payment_date'] as $column) {
                if (Schema::hasColumn('projects', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

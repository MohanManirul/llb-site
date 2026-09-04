<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The call center reads a product's name, picture and description straight from
 * inventory's own catalogue, so the CRM's hand-typed copy of the same thing had
 * nothing left to say. The create migration was already on production, which is
 * why this is a drop rather than an edit in place: deployed databases still
 * carry the table and only a migration reaches them.
 *
 * The four permissions go with it. UserSeeder syncs roles from
 * config/admin-permissions.php, so re-seeding detaches them, but the rows
 * themselves would linger and show up on the role screen as grantable
 * permissions that no route reads.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('product_details');

        DB::table('permissions')
            ->whereIn('name', [
                'view product details',
                'create product details',
                'edit product details',
                'delete product details',
            ])
            ->delete();
    }

    public function down(): void {}
};

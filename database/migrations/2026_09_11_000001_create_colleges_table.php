<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colleges', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 200)->unique();
            $table->string('name_bn', 200);
            $table->string('name_en', 200)->nullable();
            $table->string('short_name_bn', 60)->nullable();
            $table->string('short_name_en', 60)->nullable();
            $table->string('eiin_code', 20)->nullable()->index();
            $table->string('college_code', 20)->nullable()->index();
            $table->string('district_bn', 100)->nullable();
            $table->string('district_en', 100)->nullable();
            $table->string('address_bn', 255)->nullable();
            $table->string('address_en', 255)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
            $table->index('district_en');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colleges');
    }
};

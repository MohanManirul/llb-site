<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name_bn', 150)->nullable();
            $table->string('name_en', 150)->nullable();
            $table->string('slogan_bn')->nullable();
            $table->string('slogan_en')->nullable();
            $table->text('logo')->nullable();
            $table->text('favicon')->nullable();
            $table->text('whatsapp_url')->nullable();
            $table->text('facebook_url')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->timestamps();
        });

        // The row the app reads. Seeded with the values that were hard-coded in
        // resources/js/config/site.ts and the public/ image files, so the site
        // looks the same the moment this migration runs.
        DB::table('site_settings')->insert([
            'id' => 1,
            'name_bn' => 'আইন পথ',
            'name_en' => 'AinPath',
            'slogan_bn' => 'এলএলবি শিক্ষার্থীদের জন্য সেশন ও বিষয়ভিত্তিক সাজেশন, বই ও ক্লাস নোট — বিনামূল্যে।',
            'slogan_en' => 'Session and subject wise suggestions, books and class notes for LLB students — free.',
            'logo' => '/llb.jpg',
            'favicon' => '/llb_favicon.png',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};

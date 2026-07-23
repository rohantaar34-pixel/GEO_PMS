<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('settings_key')->unique()->default('default');
            $table->string('system_name')->default('ARDC Project Management System');
            $table->string('system_short_name')->default('ARDC');
            $table->string('system_tagline')->default('Management System');
            $table->string('primary_color', 7)->default('#BE0000');
            $table->string('logo_path')->nullable();
            $table->timestamps();
        });

        DB::table('system_settings')->insert([
            'settings_key' => 'default',
            'system_name' => 'ARDC Project Management System',
            'system_short_name' => 'ARDC',
            'system_tagline' => 'Management System',
            'primary_color' => '#BE0000',
            'logo_path' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};

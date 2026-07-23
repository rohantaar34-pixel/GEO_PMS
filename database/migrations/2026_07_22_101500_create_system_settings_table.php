<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('settings_key')->unique()->default('default');
            $table->string('system_name')->default('GEO CORP. Project Budget Tracking');
            $table->string('system_short_name')->default('GEO CORP.');
            $table->string('system_tagline')->default('Budget Tracking');
            $table->string('primary_color', 7)->default('#0F6FB0');
            $table->string('logo_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};

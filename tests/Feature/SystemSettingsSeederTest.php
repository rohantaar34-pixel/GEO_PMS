<?php

use App\Models\SystemSetting;
use Database\Seeders\SystemSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('system settings seeder creates the geo corp branding row when missing', function () {
    expect(SystemSetting::query()->count())->toBe(0);

    $this->seed(SystemSettingsSeeder::class);

    $settings = SystemSetting::query()->where('settings_key', 'default')->first();

    expect($settings)->not->toBeNull();
    expect($settings->system_name)->toBe('GEO CORP. Project Budget Tracking');
    expect($settings->system_short_name)->toBe('GEO CORP.');
    expect($settings->system_tagline)->toBe('Budget Tracking');
    expect($settings->primary_color)->toBe('#0F6FB0');
    expect($settings->logo_path)->toBeNull();
});

test('system settings seeder does not overwrite existing branding', function () {
    $settings = SystemSetting::query()->create([
        'settings_key' => 'default',
        'system_name' => 'Custom Branding',
        'system_short_name' => 'CUSTOM',
        'system_tagline' => 'Custom Tagline',
        'primary_color' => '#123456',
        'logo_path' => 'branding/logos/custom.png',
    ]);

    $this->seed(SystemSettingsSeeder::class);

    $settings->refresh();

    expect($settings->system_name)->toBe('Custom Branding');
    expect($settings->system_short_name)->toBe('CUSTOM');
    expect($settings->system_tagline)->toBe('Custom Tagline');
    expect($settings->primary_color)->toBe('#123456');
    expect($settings->logo_path)->toBe('branding/logos/custom.png');
});

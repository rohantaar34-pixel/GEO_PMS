<?php

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('super admin can access and update branding settings', function () {
    Storage::fake('public');

    $superAdmin = User::factory()->create([
        'role' => 'super_admin',
        'email' => 'super@example.com',
    ]);

    $this->actingAs($superAdmin)
        ->get(route('settings.branding.index'))
        ->assertOk();

    $response = $this->actingAs($superAdmin)
        ->put(route('settings.branding.update'), [
            'system_name' => 'Bacolod Infrastructure Hub',
            'system_short_name' => 'BIH',
            'system_tagline' => 'Project Control Center',
            'primary_color' => '#1D4ED8',
            'logo' => UploadedFile::fake()->image('brand-logo.png', 160, 160),
        ]);

    $response
        ->assertRedirect(route('settings.branding.index'))
        ->assertSessionHas('success');

    $settings = SystemSetting::query()->where('settings_key', 'default')->first();

    expect($settings)->not->toBeNull();
    expect($settings->system_name)->toBe('Bacolod Infrastructure Hub');
    expect($settings->system_short_name)->toBe('BIH');
    expect($settings->system_tagline)->toBe('Project Control Center');
    expect($settings->primary_color)->toBe('#1D4ED8');
    expect($settings->logo_path)->not->toBeNull();

    Storage::disk('public')->assertExists($settings->logo_path);
});

test('admin cannot access branding settings', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('settings.branding.index'))
        ->assertForbidden();
});

test('super admin can access admin-only modules', function () {
    $superAdmin = User::factory()->create(['role' => 'super_admin']);

    $this->actingAs($superAdmin)
        ->get(route('projects.index'))
        ->assertOk();
});

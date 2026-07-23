<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = SystemSetting::defaults();

        SystemSetting::query()->firstOrCreate(
            ['settings_key' => $defaults['settings_key']],
            $defaults,
        );
    }
}

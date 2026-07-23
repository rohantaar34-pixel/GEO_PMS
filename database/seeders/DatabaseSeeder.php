<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(SystemSettingsSeeder::class);

        if (! app()->isLocal()) {
            return;
        }

        User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            User::factory()->raw([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]),
        );
    }
}

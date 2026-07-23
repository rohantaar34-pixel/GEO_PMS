<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $superAdminId = DB::table('users')
            ->where('email', 'super@gmail.com')
            ->value('id');

        if ($superAdminId) {
            DB::table('users')
                ->where('id', $superAdminId)
                ->update(['role' => 'super_admin']);

            return;
        }

        $hasSuperAdmin = DB::table('users')
            ->where('role', 'super_admin')
            ->exists();

        if ($hasSuperAdmin) {
            return;
        }

        $fallbackAdminId = DB::table('users')
            ->where('role', 'admin')
            ->orderBy('id')
            ->value('id');

        if ($fallbackAdminId) {
            DB::table('users')
                ->where('id', $fallbackAdminId)
                ->update(['role' => 'super_admin']);
        }
    }

    public function down(): void
    {
        DB::table('users')
            ->where('email', 'super@gmail.com')
            ->where('role', 'super_admin')
            ->update(['role' => 'admin']);
    }
};

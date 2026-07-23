<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('projects')) {
            Schema::create('projects', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('budget', 12, 2)->default(0);
                $table->timestamps();
            });

            return;
        }

        $missingBudget = ! Schema::hasColumn('projects', 'budget');
        $missingUpdatedAt = ! Schema::hasColumn('projects', 'updated_at');

        if (! $missingBudget && ! $missingUpdatedAt) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) use ($missingBudget, $missingUpdatedAt) {
            if ($missingBudget) {
                $table->decimal('budget', 12, 2)->default(0);
            }

            if ($missingUpdatedAt) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};

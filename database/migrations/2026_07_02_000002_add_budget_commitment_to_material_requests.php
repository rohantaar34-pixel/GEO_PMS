<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_requests', function (Blueprint $table) {
            $table->decimal('estimated_unit_cost', 15, 2)->nullable()->after('procurement_note');
            $table->decimal('estimated_total_cost', 15, 2)->nullable()->after('estimated_unit_cost');
            $table->decimal('actual_total_cost', 15, 2)->nullable()->after('estimated_total_cost');
            $table->string('budget_commitment_status')->nullable()->after('actual_total_cost');
            $table->foreignId('budget_transaction_id')
                ->nullable()
                ->after('budget_commitment_status')
                ->constrained('transactions')
                ->nullOnDelete();

            $table->index(['project_id', 'budget_commitment_status']);
        });
    }

    public function down(): void
    {
        Schema::table('material_requests', function (Blueprint $table) {
            $table->dropIndex(['project_id', 'budget_commitment_status']);
            $table->dropConstrainedForeignId('budget_transaction_id');
            $table->dropColumn([
                'estimated_unit_cost',
                'estimated_total_cost',
                'actual_total_cost',
                'budget_commitment_status',
            ]);
        });
    }
};

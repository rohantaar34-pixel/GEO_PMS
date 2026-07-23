<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transactions')) {
            Schema::create('transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained()->onDelete('cascade');
                $table->enum('type', ['income', 'expense']);
                $table->string('category');
                $table->decimal('amount', 12, 2);
                $table->text('description')->nullable();
                $table->date('transaction_date');
                $table->string('vendor_or_client')->nullable();
                $table->string('invoice_ref')->nullable();
                $table->timestamps();

                $table->index(['project_id', 'type', 'category']);
            });

            return;
        }

        $this->normalizeLegacyProjectKeyTypes();

        $missingVendorOrClient = ! Schema::hasColumn('transactions', 'vendor_or_client');
        $missingInvoiceRef = ! Schema::hasColumn('transactions', 'invoice_ref');
        $missingUpdatedAt = ! Schema::hasColumn('transactions', 'updated_at');

        if ($missingVendorOrClient || $missingInvoiceRef || $missingUpdatedAt) {
            Schema::table('transactions', function (Blueprint $table) use ($missingVendorOrClient, $missingInvoiceRef, $missingUpdatedAt) {
                if ($missingVendorOrClient) {
                    $table->string('vendor_or_client')->nullable();
                }

                if ($missingInvoiceRef) {
                    $table->string('invoice_ref')->nullable();
                }

                if ($missingUpdatedAt) {
                    $table->timestamp('updated_at')->nullable();
                }
            });
        }
    }

    private function normalizeLegacyProjectKeyTypes(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        $projectIdType = $this->mysqlColumnType('projects', 'id');
        $transactionIdType = $this->mysqlColumnType('transactions', 'id');
        $transactionProjectIdType = $this->mysqlColumnType('transactions', 'project_id');

        $needsTypeNormalization = ! $this->isUnsignedBigInt($projectIdType)
            || ! $this->isUnsignedBigInt($transactionIdType)
            || ! $this->isUnsignedBigInt($transactionProjectIdType);

        if (! $needsTypeNormalization) {
            return;
        }

        $database = DB::getDatabaseName();
        $foreignKeys = DB::select(
            <<<'SQL'
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME = 'transactions'
              AND COLUMN_NAME = 'project_id'
              AND REFERENCED_TABLE_NAME IS NOT NULL
            SQL,
            [$database]
        );

        foreach ($foreignKeys as $foreignKey) {
            DB::statement("ALTER TABLE `transactions` DROP FOREIGN KEY `{$foreignKey->CONSTRAINT_NAME}`");
        }

        DB::statement('ALTER TABLE `projects` MODIFY COLUMN `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
        DB::statement('ALTER TABLE `transactions` MODIFY COLUMN `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
        DB::statement('ALTER TABLE `transactions` MODIFY COLUMN `project_id` BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE `transactions` ADD CONSTRAINT `transactions_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE');
    }

    private function mysqlColumnType(string $table, string $column): ?string
    {
        $columnDefinition = DB::selectOne(
            <<<'SQL'
            SELECT COLUMN_TYPE
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
            SQL,
            [DB::getDatabaseName(), $table, $column]
        );

        return $columnDefinition ? strtolower($columnDefinition->COLUMN_TYPE) : null;
    }

    private function isUnsignedBigInt(?string $columnType): bool
    {
        return $columnType !== null
            && str_contains($columnType, 'bigint')
            && str_contains($columnType, 'unsigned');
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

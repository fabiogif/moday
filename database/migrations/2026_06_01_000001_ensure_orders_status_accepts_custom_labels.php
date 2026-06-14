<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('orders') || !Schema::hasColumn('orders', 'status')) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $column = DB::selectOne("
            SELECT DATA_TYPE, COLUMN_TYPE
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'orders'
              AND COLUMN_NAME = 'status'
        ");

        if (!$column || strtolower((string) $column->DATA_TYPE) !== 'enum') {
            return;
        }

        DB::statement('ALTER TABLE `orders` MODIFY COLUMN `status` VARCHAR(100) NOT NULL DEFAULT \'Em Preparo\'');
    }

    public function down(): void
    {
        // Não reverte para ENUM para evitar truncar status personalizados.
    }
};

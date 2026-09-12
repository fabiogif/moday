<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `orders.origin` foi criada por 2025_10_06_200521_add_origin_to_orders_table
 * como ENUM('admin', 'public_store'). PublicOrderService grava 'store' para
 * pedidos do cardápio público, valor fora do enum — em modo estrito o MySQL
 * rejeita o insert com "Data truncated for column 'origin'", derrubando o
 * checkout público inteiro (SQLSTATE[01000] 1265). Mesma classe de problema
 * já corrigida para `orders.status` em
 * 2026_06_01_000001_ensure_orders_status_accepts_custom_labels.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('orders') || !Schema::hasColumn('orders', 'origin')) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $column = DB::selectOne("
            SELECT DATA_TYPE
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'orders'
              AND COLUMN_NAME = 'origin'
        ");

        if (!$column || strtolower((string) $column->DATA_TYPE) !== 'enum') {
            return;
        }

        DB::statement('ALTER TABLE `orders` MODIFY COLUMN `origin` VARCHAR(30) NOT NULL DEFAULT \'admin\'');
    }

    public function down(): void
    {
        // Não reverte para ENUM para evitar truncar origins personalizadas.
    }
};

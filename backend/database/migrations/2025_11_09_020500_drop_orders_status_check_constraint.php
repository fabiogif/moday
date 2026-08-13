<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('orders')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_status_check');
            return;
        }

        if ($driver === 'mysql') {
            $database = DB::getDatabaseName();
            $checkExists = DB::selectOne("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE CONSTRAINT_SCHEMA = ? 
                  AND TABLE_NAME = 'orders' 
                  AND CONSTRAINT_NAME = 'orders_status_check'
                  AND CONSTRAINT_TYPE = 'CHECK'
            ", [$database]);

            if ($checkExists) {
                DB::statement('ALTER TABLE `orders` DROP CHECK `orders_status_check`');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Não recriamos o constraint para evitar bloquear novos status.
        // Caso necessário, adicione novamente através de uma migração específica.
    }
};


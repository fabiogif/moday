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

        if ($driver === 'mysql') {
            // Aumentar o tamanho da coluna status para comportar nomes mais longos
            // Ex: "Pedido Recebido", "Em Preparação", "Pronto para Expedição", etc.
            DB::statement('ALTER TABLE `orders` MODIFY COLUMN `status` VARCHAR(100) DEFAULT "Pendente"');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE orders ALTER COLUMN status TYPE VARCHAR(100)');
            DB::statement('ALTER TABLE orders ALTER COLUMN status SET DEFAULT \'Pendente\'');
        } elseif ($driver === 'sqlite') {
            // SQLite não suporta MODIFY COLUMN diretamente
            // Seria necessário recriar a tabela, mas vamos apenas logar um aviso
            \Log::warning('SQLite não suporta alteração de tamanho de coluna. A coluna status pode precisar ser recriada manualmente.');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('orders')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            // Reverter para o tamanho padrão (255 caracteres)
            DB::statement('ALTER TABLE `orders` MODIFY COLUMN `status` VARCHAR(255) DEFAULT "Pendente"');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE orders ALTER COLUMN status TYPE VARCHAR(255)');
            DB::statement('ALTER TABLE orders ALTER COLUMN status SET DEFAULT \'Pendente\'');
        }
    }
};

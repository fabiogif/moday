<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Atualizar dados existentes para os novos status
        DB::statement("UPDATE orders SET status = 'Em Preparo' WHERE status IN ('Pendente', 'Em Andamento')");
        
        // Verificar se é MySQL
        if (DB::getDriverName() === 'mysql') {
            // Alterar o ENUM para os novos status
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM(
                'Em Preparo',
                'Pronto',
                'Entregue',
                'Cancelado'
            ) DEFAULT 'Em Preparo'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverter para o status anterior
        DB::statement("UPDATE orders SET status = 'Pendente' WHERE status = 'Em Preparo'");
        
        // Verificar se é MySQL
        if (DB::getDriverName() === 'mysql') {
            // Voltar ao ENUM anterior
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM(
                'Pendente',
                'Completo',
                'Cancelado',
                'Rejeitado',
                'Em Andamento',
                'Em Entrega'
            ) DEFAULT 'Pendente'");
        }
    }
};

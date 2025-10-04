<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE orders 
            MODIFY COLUMN status ENUM(
                'Preparo', 
                'Pronto', 
                'Entregue', 
                'Pendente', 
                'Em Andamento', 
                'Em Preparo', 
                'Completo', 
                'Cancelado', 
                'Rejeitado', 
                'Em Entrega'
            ) DEFAULT 'Pendente'
        ");
    }
    
    public function down(): void
    {
        // Aqui você deve colocar o ENUM que existia antes da alteração
        DB::statement("
            ALTER TABLE orders 
            MODIFY COLUMN status ENUM(
                'Preparo', 
                'Pronto', 
                'Entregue'
            ) DEFAULT 'Preparo'
        ");
    }
}




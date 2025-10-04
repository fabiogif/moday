<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            // Adiciona campo is_active se não existir
            if (!Schema::hasColumn('profiles', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('description');
            }
            
            // Adiciona índices para melhor performance
            $table->index(['is_active']);
            $table->index(['name', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            // Verifica se a coluna existe antes de tentar remover
            if (Schema::hasColumn('profiles', 'is_active')) {
                // Remove índices usando try-catch para evitar erros se não existirem
                try {
                    $table->dropIndex('profiles_is_active_index');
                } catch (\Exception $e) {
                    // Índice não existe, continua
                }
                
                try {
                    $table->dropIndex('profiles_name_is_active_index');
                } catch (\Exception $e) {
                    // Índice não existe, continua
                }
                
                $table->dropColumn('is_active');
            }
        });
    }
};

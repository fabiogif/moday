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
        if (!Schema::hasTable('permissions')) {
            return; // Se a tabela base não existe, apenas sai (outra migration criará a tabela)
        }

        Schema::table('permissions', function (Blueprint $table) {
            // Adiciona novos campos se não existirem
            if (!Schema::hasColumn('permissions', 'group')) {
                $table->string('group')->nullable()->after('description');
                $table->index(['group']);
            }
            
            if (!Schema::hasColumn('permissions', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('group');
                $table->index(['is_active']);
            }

            // Índice composto em name + is_active (criado apenas quando adicionamos is_active agora)
            if (Schema::hasColumn('permissions', 'is_active')) {
                $table->index(['name', 'is_active']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        Schema::table('permissions', function (Blueprint $table) {
            // Ao dropar as colunas, os índices associados caem automaticamente no MySQL
            if (Schema::hasColumn('permissions', 'group')) {
                $table->dropColumn('group');
            }
            if (Schema::hasColumn('permissions', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};

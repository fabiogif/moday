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
        // Corrigir tabela categories - só se a tabela existir
        if (Schema::hasTable('categories')) {
            Schema::table('categories', function (Blueprint $table) {
                // Tornar url nullable ou com valor padrão
                if (Schema::hasColumn('categories', 'url')) {
                    $table->string('url')->nullable()->change();
                }
                
                // Adicionar status padrão se não existir
                if (!Schema::hasColumn('categories', 'status')) {
                    $table->enum('status', ['A', 'I'])->default('A');
                }
            });
        }

        // Corrigir tabela products - só se a tabela existir
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                // Tornar campos opcionais se necessário
                if (Schema::hasColumn('products', 'flag')) {
                    $table->string('flag')->nullable()->change();
                }
                if (Schema::hasColumn('products', 'image')) {
                    $table->string('image')->nullable()->change();
                }
            });
        }

        // Corrigir tabela tables - só se a tabela existir
        if (Schema::hasTable('tables')) {
            Schema::table('tables', function (Blueprint $table) {
                // Tornar campos opcionais se necessário
                if (Schema::hasColumn('tables', 'identify')) {
                    $table->string('identify')->nullable()->change();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverter mudanças se necessário - só se as tabelas existirem
        if (Schema::hasTable('categories')) {
            Schema::table('categories', function (Blueprint $table) {
                if (Schema::hasColumn('categories', 'url')) {
                    $table->string('url')->nullable(false)->change();
                }
            });
        }

        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (Schema::hasColumn('products', 'flag')) {
                    $table->string('flag')->nullable(false)->change();
                }
                if (Schema::hasColumn('products', 'image')) {
                    $table->string('image')->nullable(false)->change();
                }
            });
        }

        if (Schema::hasTable('tables')) {
            Schema::table('tables', function (Blueprint $table) {
                if (Schema::hasColumn('tables', 'identify')) {
                    $table->string('identify')->nullable(false)->change();
                }
            });
        }
    }
};

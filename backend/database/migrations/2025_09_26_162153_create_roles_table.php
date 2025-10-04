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
        // Verificar se a tabela já existe antes de criá-la
        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->integer('level')->default(5); // 1=Super Admin, 2=Admin, 3=Manager, 4=User, 5=Guest
                $table->boolean('is_active')->default(true);
                $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');
                $table->timestamps();
                
                $table->index(['tenant_id', 'is_active']);
                $table->index('level');
            });
        } else {
            // Se a tabela já existe, adicionar campos que faltam
            Schema::table('roles', function (Blueprint $table) {
                // Adicionar description se não existir
                if (!Schema::hasColumn('roles', 'description')) {
                    $table->text('description')->nullable()->after('slug');
                }
                
                // Adicionar level após description (ou após slug se description não existir)
                if (!Schema::hasColumn('roles', 'level')) {
                    if (Schema::hasColumn('roles', 'description')) {
                        $table->integer('level')->default(5)->after('description');
                    } else {
                        $table->integer('level')->default(5)->after('slug');
                    }
                }
                
                // Adicionar is_active após level
                if (!Schema::hasColumn('roles', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('level');
                }
                
                // Adicionar tenant_id após is_active
                if (!Schema::hasColumn('roles', 'tenant_id')) {
                    $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade')->after('is_active');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};

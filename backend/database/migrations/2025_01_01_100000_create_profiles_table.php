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
        if (!Schema::hasTable('profiles')) {
            Schema::create('profiles', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name')->unique();
                $table->string('description')->nullable();
                $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');
                $table->timestamps();
                $table->softDeletes();
            });
        } else {
            // Se a tabela já existe, adicionar campos que faltam
            Schema::table('profiles', function (Blueprint $table) {
                if (!Schema::hasColumn('profiles', 'deleted_at')) {
                    $table->softDeletes();
                }
                if (!Schema::hasColumn('profiles', 'tenant_id')) {
                    $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade')->after('description');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remover tabelas que referenciam profiles primeiro
        Schema::dropIfExists('plan_profile');
        Schema::dropIfExists('permission_profiles');
        Schema::dropIfExists('permission_profile');
        Schema::dropIfExists('profile_permission');
        Schema::dropIfExists('profile_user');
        Schema::dropIfExists('user_profiles');
        
        // Não remover a tabela profiles se ela for referenciada por users
        // pois isso pode causar problemas de integridade
        // A tabela profiles será removida por outras migrations se necessário
    }
};

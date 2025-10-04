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
        // Verificar se a tabela já existe
        if (!Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('module')->nullable();
                $table->string('action')->nullable();
                $table->string('resource')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');
                $table->timestamps();
                
                $table->index(['module', 'action', 'resource']);
                $table->index('is_active');
                $table->index(['tenant_id', 'is_active']);
            });
        } else {
            // Se a tabela já existe, adicionar campos que faltam
            Schema::table('permissions', function (Blueprint $table) {
                if (!Schema::hasColumn('permissions', 'slug')) {
                    $table->string('slug')->unique()->after('name');
                }
                if (!Schema::hasColumn('permissions', 'module')) {
                    $table->string('module')->nullable()->after('description');
                }
                if (!Schema::hasColumn('permissions', 'action')) {
                    $table->string('action')->nullable()->after('module');
                }
                if (!Schema::hasColumn('permissions', 'resource')) {
                    $table->string('resource')->nullable()->after('action');
                }
                if (!Schema::hasColumn('permissions', 'tenant_id')) {
                    $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade')->after('is_active');
                    $table->index(['tenant_id', 'is_active']);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remover pivôs que referenciam permissions antes
        if (Schema::hasTable('permission_profiles')) {
            Schema::drop('permission_profiles');
        }
        if (Schema::hasTable('profile_permission')) {
            Schema::drop('profile_permission');
        }
        if (Schema::hasTable('permission_profile')) {
            Schema::drop('permission_profile');
        }
        Schema::dropIfExists('permissions');
    }
};

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
        // Padroniza a tabela pivô como 'permission_profiles'
        if (Schema::hasTable('permission_profile') && !Schema::hasTable('permission_profiles')) {
            Schema::rename('permission_profile', 'permission_profiles');
        }

        if (!Schema::hasTable('permission_profiles')) {
            Schema::create('permission_profiles', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('permission_id');
                $table->unsignedBigInteger('profile_id');
                $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
                $table->foreign('profile_id')->references('id')->on('profiles')->onDelete('cascade');
                $table->unique(['permission_id', 'profile_id']);
                $table->index(['permission_id']);
                $table->index(['profile_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop pivô padronizada primeiro
        if (Schema::hasTable('permission_profiles')) {
            Schema::drop('permission_profiles');
        }
        // Garantia para antigo nome, se existir
        if (Schema::hasTable('permission_profile')) {
            Schema::drop('permission_profile');
        }
    }
};

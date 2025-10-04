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
        // Cria tabela pivot entre profiles e permissions se não existir
        if (!Schema::hasTable('profile_permission')) {
            Schema::create('profile_permission', function (Blueprint $table) {
                $table->id();
                $table->foreignId('profile_id')->constrained()->onDelete('cascade');
                $table->foreignId('permission_id')->constrained()->onDelete('cascade');
                $table->timestamps();
                
                $table->unique(['profile_id', 'permission_id']);
                $table->index(['profile_id']);
                $table->index(['permission_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile_permission');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Idempotente: em produção a tabela pode já existir via schema legado /
     * create_core_tables sem este batch estar registrado em `migrations`.
     */
    public function up(): void
    {
        if (Schema::hasTable('plans')) {
            return;
        }

        Schema::create('plans', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->unique();
            $table->string('url')->unique();
            $table->string('description')->nullable();
            $table->double('price', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};

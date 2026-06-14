<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_types', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('identify')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_address')->default(false);
            $table->boolean('requires_table')->default(false);
            $table->boolean('available_in_menu')->default(true);
            $table->integer('order_position')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('uuid');
            $table->index('identify');
            $table->index('slug');
            $table->index('is_active');
            $table->index('available_in_menu');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_types');
    }
};














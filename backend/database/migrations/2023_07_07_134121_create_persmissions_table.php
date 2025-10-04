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
        Schema::create('permissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });
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

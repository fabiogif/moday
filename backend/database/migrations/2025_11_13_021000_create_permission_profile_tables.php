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
        if (!Schema::hasTable('permission_profiles')) {
            Schema::create('permission_profiles', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
                $table->foreignId('profile_id')->constrained('profiles')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['permission_id', 'profile_id'], 'permission_profiles_unique');
                $table->index('permission_id');
                $table->index('profile_id');
            });
        }

        if (!Schema::hasTable('user_profiles')) {
            Schema::create('user_profiles', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('profile_id')->constrained('profiles')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['user_id', 'profile_id'], 'user_profiles_unique');
                $table->index('user_id');
                $table->index('profile_id');
            });
        }

        if (!Schema::hasTable('user_permissions')) {
            Schema::create('user_permissions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['user_id', 'permission_id'], 'user_permissions_unique');
                $table->index('user_id');
                $table->index('permission_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_permissions');
        Schema::dropIfExists('user_profiles');
        Schema::dropIfExists('permission_profiles');
    }
};


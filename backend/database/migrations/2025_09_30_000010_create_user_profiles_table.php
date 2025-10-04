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
        if (!Schema::hasTable('user_profiles')) {
            Schema::create('user_profiles', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('profile_id');
                $table->timestamps();

                $table->primary(['user_id', 'profile_id']);

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('profile_id')->references('id')->on('profiles')->onDelete('cascade');

                $table->index(['user_id']);
                $table->index(['profile_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('user_profiles')) {
            Schema::drop('user_profiles');
        }
    }
};



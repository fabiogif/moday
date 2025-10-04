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
        Schema::table('users', function (Blueprint $table) {
            // Adiciona novos campos se não existirem
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }
            
            if (!Schema::hasColumn('users', 'avatar')) {
                $table->string('avatar')->nullable()->after('phone');
            }
            
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('avatar');
            }
            
            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('email_verified_at');
            }
            
            if (!Schema::hasColumn('users', 'preferences')) {
                $table->json('preferences')->nullable()->after('last_login_at');
            }
            
            if (!Schema::hasColumn('users', 'profile_id')) {
                $table->foreignId('profile_id')->nullable()->after('tenant_id')->constrained()->onDelete('set null');
            }
            
            if (!Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'avatar', 
                'is_active',
                'last_login_at',
                'preferences',
                'deleted_at'
            ]);
            
            if (Schema::hasColumn('users', 'profile_id')) {
                $table->dropForeign(['profile_id']);
                $table->dropColumn('profile_id');
            }
        });
    }
};

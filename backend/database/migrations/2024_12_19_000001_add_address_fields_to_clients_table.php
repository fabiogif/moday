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
        Schema::table('clients', function (Blueprint $table) {
            // Verifica se as colunas já existem antes de tentar criá-las
            if (!Schema::hasColumn('clients', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }
            if (!Schema::hasColumn('clients', 'address')) {
                $table->string('address')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('clients', 'city')) {
                $table->string('city')->nullable()->after('address');
            }
            if (!Schema::hasColumn('clients', 'state')) {
                $table->string('state')->nullable()->after('city');
            }
            if (!Schema::hasColumn('clients', 'zip_code')) {
                $table->string('zip_code')->nullable()->after('state');
            }
            if (!Schema::hasColumn('clients', 'neighborhood')) {
                $table->string('neighborhood')->nullable()->after('zip_code');
            }
            if (!Schema::hasColumn('clients', 'number')) {
                $table->string('number')->nullable()->after('neighborhood');
            }
            if (!Schema::hasColumn('clients', 'complement')) {
                $table->string('complement')->nullable()->after('number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'address',
                'city',
                'state',
                'zip_code',
                'neighborhood',
                'number',
                'complement'
            ]);
        });
    }
};
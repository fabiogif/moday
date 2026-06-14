<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (!Schema::hasColumn('plans', 'periodicity')) {
                $table->enum('periodicity', ['monthly', 'quarterly', 'semiannual', 'annual'])
                    ->default('monthly')
                    ->after('price');
            }
            if (!Schema::hasColumn('plans', 'max_branches')) {
                $table->unsignedInteger('max_branches')->nullable()->after('max_products');
            }
            if (!Schema::hasColumn('plans', 'support_type')) {
                $table->enum('support_type', ['community', 'email', 'priority', 'dedicated'])
                    ->default('community')
                    ->after('max_branches');
            }
            if (!Schema::hasColumn('plans', 'integrations')) {
                $table->json('integrations')->nullable()->after('support_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['periodicity', 'max_branches', 'support_type', 'integrations']);
        });
    }
};

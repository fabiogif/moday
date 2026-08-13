<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('states', function (Blueprint $table) {
            if (!Schema::hasColumn('states', 'ibge_code')) {
                $table->string('ibge_code', 10)->nullable()->after('id');
            }
            if (!Schema::hasColumn('states', 'region')) {
                $table->string('region', 50)->nullable()->after('name');
            }
        });

        $stateIndexes = collect(Schema::getIndexes('states'))->pluck('name');
        if (!$stateIndexes->contains('states_ibge_code_unique') && Schema::hasColumn('states', 'ibge_code')) {
            Schema::table('states', function (Blueprint $table) {
                $table->unique('ibge_code');
            });
        }

        Schema::table('cities', function (Blueprint $table) {
            if (!Schema::hasColumn('cities', 'ibge_code')) {
                $table->string('ibge_code', 10)->nullable()->after('state_id');
            }
        });

        $cityIndexes = collect(Schema::getIndexes('cities'))->pluck('name');
        Schema::table('cities', function (Blueprint $table) use ($cityIndexes) {
            if (!$cityIndexes->contains('cities_ibge_code_unique') && Schema::hasColumn('cities', 'ibge_code')) {
                $table->unique('ibge_code');
            }
            if (!$cityIndexes->contains('cities_name_index')) {
                $table->index('name');
            }
        });
    }

    public function down(): void
    {
        $cityIndexes = collect(Schema::getIndexes('cities'))->pluck('name');
        Schema::table('cities', function (Blueprint $table) use ($cityIndexes) {
            if ($cityIndexes->contains('cities_ibge_code_unique')) {
                $table->dropUnique(['ibge_code']);
            }
            if ($cityIndexes->contains('cities_name_index')) {
                $table->dropIndex(['name']);
            }
            if (Schema::hasColumn('cities', 'ibge_code')) {
                $table->dropColumn('ibge_code');
            }
        });

        $stateIndexes = collect(Schema::getIndexes('states'))->pluck('name');
        Schema::table('states', function (Blueprint $table) use ($stateIndexes) {
            if ($stateIndexes->contains('states_ibge_code_unique')) {
                $table->dropUnique(['ibge_code']);
            }
            $cols = array_values(array_filter([
                Schema::hasColumn('states', 'ibge_code') ? 'ibge_code' : null,
                Schema::hasColumn('states', 'region') ? 'region' : null,
            ]));
            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });
    }
};

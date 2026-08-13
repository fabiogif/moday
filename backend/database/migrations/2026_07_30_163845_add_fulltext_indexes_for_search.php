<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Índices FULLTEXT para os campos de texto livre usados em busca (nome,
 * empresa, descrição, marca). Campos de código/identificador (SKU, código
 * de barras, telefone, e-mail, UUID, subdomínio, código de cupom, identify)
 * continuam em LIKE — FULLTEXT casa por palavra/prefixo, não por substring
 * no meio da string, o que quebraria a busca parcial nesses campos.
 *
 * MySQL/MariaDB apenas: SQLite (usado nos testes) não suporta FULLTEXT: os
 * repositories fazem fallback para LIKE nesse caso (ver
 * App\Repositories\Concerns\SearchesFullText).
 */
return new class extends Migration
{
    private array $indexes = [
        'clients' => [
            ['name'],
            ['name', 'company_name', 'trade_name'],
        ],
        'products' => [
            ['name'],
            ['name', 'brand'],
        ],
        'categories' => [
            ['name'],
        ],
        'profiles' => [
            ['name'],
        ],
        'permissions' => [
            ['name'],
        ],
        'users' => [
            ['name'],
        ],
        'plans' => [
            ['name'],
        ],
        'service_types' => [
            ['name'],
        ],
        'coupons' => [
            ['name'],
            ['name', 'description'],
        ],
        'tenants' => [
            ['name'],
        ],
        'cities' => [
            ['name'],
        ],
    ];

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        foreach ($this->indexes as $table => $columnSets) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table, $columnSets) {
                foreach ($columnSets as $columns) {
                    $indexName = $table . '_' . implode('_', $columns) . '_fulltext';
                    if (!$this->indexExists($table, $indexName)) {
                        $blueprint->fullText($columns, $indexName);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        foreach ($this->indexes as $table => $columnSets) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table, $columnSets) {
                foreach ($columnSets as $columns) {
                    $indexName = $table . '_' . implode('_', $columns) . '_fulltext';
                    if ($this->indexExists($table, $indexName)) {
                        $blueprint->dropFullText($indexName);
                    }
                }
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $result = DB::select('SHOW INDEX FROM `' . $table . '` WHERE Key_name = ?', [$indexName]);
        return !empty($result);
    }
};

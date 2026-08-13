<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'volume')) {
                $table->decimal('volume', 12, 4)->nullable()->after('depth');
            }
            if (!Schema::hasColumn('products', 'image_url')) {
                $table->string('image_url', 500)->nullable()->after('image');
            }
        });

        if (!$this->indexExists('products', 'idx_produto_codigo_barras') && !$this->hasDuplicateBarcodes()) {
            try {
                Schema::table('products', function (Blueprint $table) {
                    $table->unique(['tenant_id', 'barcode'], 'idx_produto_codigo_barras');
                });
            } catch (\Throwable) {
                // Ambientes com dados legados / índice equivalente
            }
        }

        Schema::create('barcode_lookups', function (Blueprint $table) {
            $table->id();
            $table->string('barcode', 20)->unique();
            $table->string('source', 40);
            $table->string('name')->nullable();
            $table->string('brand', 150)->nullable();
            $table->string('category', 150)->nullable();
            $table->string('unit_of_measure', 50)->nullable();
            $table->decimal('weight', 12, 4)->nullable();
            $table->decimal('volume', 12, 4)->nullable();
            $table->string('image_url', 500)->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barcode_lookups');

        if ($this->indexExists('products', 'idx_produto_codigo_barras')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropUnique('idx_produto_codigo_barras');
            });
        }

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'volume')) {
                $table->dropColumn('volume');
            }
            if (Schema::hasColumn('products', 'image_url')) {
                $table->dropColumn('image_url');
            }
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");
            foreach ($indexes as $index) {
                if (($index->name ?? null) === $indexName) {
                    return true;
                }
            }

            return false;
        }

        $database = DB::getDatabaseName();
        $result = DB::selectOne(
            'SELECT COUNT(*) AS aggregate FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $indexName]
        );

        return ((int) ($result->aggregate ?? 0)) > 0;
    }

    private function hasDuplicateBarcodes(): bool
    {
        return DB::table('products')
            ->select('tenant_id', 'barcode')
            ->whereNotNull('barcode')
            ->where('barcode', '!=', '')
            ->groupBy('tenant_id', 'barcode')
            ->havingRaw('COUNT(*) > 1')
            ->limit(1)
            ->exists();
    }
};

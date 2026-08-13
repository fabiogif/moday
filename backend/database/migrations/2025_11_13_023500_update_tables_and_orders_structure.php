<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tables')) {
            Schema::table('tables', function (Blueprint $table) {
                if (!Schema::hasColumn('tables', 'identify')) {
                    $table->string('identify')->nullable()->after('uuid');
                }
                if (!Schema::hasColumn('tables', 'capacity')) {
                    $table->string('capacity')->default('0')->after('identify');
                }
                if (!Schema::hasColumn('tables', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('description');
                }
            });
        }

        if (Schema::hasTable('orders')) {
            $self = $this;
            Schema::table('orders', function (Blueprint $table) use ($self) {
                if (!Schema::hasColumn('orders', 'payment_method')) {
                    $table->string('payment_method')->nullable()->after('status');
                }
                if (!Schema::hasColumn('orders', 'payment_method_id')) {
                    $table->uuid('payment_method_id')->nullable()->after('payment_method');
                }

                if (!$self->indexExists('orders', 'orders_payment_method_id_index')) {
                    $table->index('payment_method_id', 'orders_payment_method_id_index');
                }

                if (!$self->foreignKeyExists('orders', 'orders_payment_method_id_foreign')) {
                    $table->foreign('payment_method_id')
                        ->references('uuid')
                        ->on('payment_methods')
                        ->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tables')) {
            Schema::table('tables', function (Blueprint $table) {
                if (Schema::hasColumn('tables', 'identify')) {
                    $table->dropColumn('identify');
                }
                if (Schema::hasColumn('tables', 'capacity')) {
                    $table->dropColumn('capacity');
                }
                if (Schema::hasColumn('tables', 'is_active')) {
                    $table->dropColumn('is_active');
                }
            });
        }

        if (Schema::hasTable('orders')) {
            $self = $this;
            Schema::table('orders', function (Blueprint $table) use ($self) {
                if (Schema::hasColumn('orders', 'payment_method_id')) {
                    if ($self->foreignKeyExists('orders', 'orders_payment_method_id_foreign')) {
                        $table->dropForeign('orders_payment_method_id_foreign');
                    }
                    if ($self->indexExists('orders', 'orders_payment_method_id_index')) {
                        $table->dropIndex('orders_payment_method_id_index');
                    }
                    $table->dropColumn('payment_method_id');
                }
                if (Schema::hasColumn('orders', 'payment_method')) {
                    $table->dropColumn('payment_method');
                }
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();
        $database = $connection->getDatabaseName();
        $prefix = $connection->getTablePrefix();

        if ($driver === 'sqlite') {
            return false;
        }

        if ($driver === 'pgsql') {
            $schema = $connection->getConfig('schema') ?? 'public';
            $result = $connection->select(
                'SELECT COUNT(*) as count FROM pg_indexes WHERE schemaname = ? AND tablename = ? AND indexname = ?',
                [$schema, $prefix . $table, $indexName]
            );

            return !empty($result) && (int) $result[0]->count > 0;
        }

        $result = $connection->select(
            'SELECT COUNT(*) as count FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $prefix . $table, $indexName]
        );

        return !empty($result) && (int) $result[0]->count > 0;
    }

    private function foreignKeyExists(string $table, string $constraintName): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();
        $database = $connection->getDatabaseName();
        $prefix = $connection->getTablePrefix();

        if ($driver === 'sqlite') {
            return false;
        }

        if ($driver === 'pgsql') {
            $schema = $connection->getConfig('schema') ?? 'public';
            $result = $connection->select(
                'SELECT COUNT(*) as count FROM information_schema.table_constraints WHERE table_schema = ? AND table_name = ? AND constraint_name = ? AND constraint_type = ?',
                [$schema, $prefix . $table, $constraintName, 'FOREIGN KEY']
            );

            return !empty($result) && (int) $result[0]->count > 0;
        }

        $result = $connection->select(
            'SELECT COUNT(*) as count FROM information_schema.table_constraints WHERE table_schema = ? AND table_name = ? AND constraint_name = ? AND constraint_type = ?',
            [$database, $prefix . $table, $constraintName, 'FOREIGN KEY']
        );

        return !empty($result) && (int) $result[0]->count > 0;
    }
};


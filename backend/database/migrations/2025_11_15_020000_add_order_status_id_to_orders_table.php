<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $driver = Schema::getConnection()->getDriverName();

            if (!Schema::hasColumn('orders', 'order_status_id')) {
                if ($driver === 'sqlite') {
                    $table->unsignedBigInteger('order_status_id')->nullable()->after('status');
                } else {
                    $table->foreignId('order_status_id')
                        ->nullable()
                        ->after('status')
                        ->constrained('order_statuses')
                        ->nullOnDelete();
                }
            }

            if (!Schema::hasColumn('orders', 'origin')) {
                $table->string('origin')->default('admin')->after('order_status_id');
            }

            if (!Schema::hasColumn('orders', 'coupon_code')) {
                $table->string('coupon_code', 40)->nullable();
            }

            if (!Schema::hasColumn('orders', 'coupon_name')) {
                $table->string('coupon_name')->nullable();
            }

            if (!Schema::hasColumn('orders', 'payment_method')) {
                $table->string('payment_method')->nullable();
            }

            if (!Schema::hasColumn('orders', 'payment_method_id')) {
                $table->uuid('payment_method_id')->nullable();

                if ($driver !== 'sqlite' && Schema::hasTable('payment_methods')) {
                    $table->foreign('payment_method_id', 'orders_payment_method_id_foreign')
                        ->references('uuid')
                        ->on('payment_methods')
                        ->nullOnDelete();
                }
            }

            if (!Schema::hasColumn('orders', 'shipping_method')) {
                $table->string('shipping_method')->nullable()->after('payment_method_id');
            }

            $deliveryColumns = [
                'delivery_address',
                'delivery_city',
                'delivery_state',
                'delivery_zip_code',
                'delivery_neighborhood',
                'delivery_number',
                'delivery_complement',
            ];

            foreach ($deliveryColumns as $column) {
                if (!Schema::hasColumn('orders', $column)) {
                    $table->string($column)->nullable();
                }
            }

            if (!Schema::hasColumn('orders', 'delivery_notes')) {
                $table->text('delivery_notes')->nullable();
            }

            if (!Schema::hasColumn('orders', 'comment')) {
                $table->text('comment')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('orders') || !Schema::hasColumn('orders', 'order_status_id')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                if ($this->hasForeignKey('orders', 'orders_order_status_id_foreign')) {
                    $table->dropForeign('orders_order_status_id_foreign');
                }
                if ($this->hasForeignKey('orders', 'orders_payment_method_id_foreign')) {
                    $table->dropForeign('orders_payment_method_id_foreign');
                }
            }

            $columns = [
                'order_status_id',
                'origin',
                'coupon_code',
                'coupon_name',
                'payment_method',
                'payment_method_id',
                'shipping_method',
                'delivery_address',
                'delivery_city',
                'delivery_state',
                'delivery_zip_code',
                'delivery_neighborhood',
                'delivery_number',
                'delivery_complement',
                'delivery_notes',
                'comment',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function hasForeignKey(string $table, string $constraintName): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            return false;
        }

        $database = $connection->getDatabaseName();
        $prefix = $connection->getTablePrefix();

        if ($driver === 'pgsql') {
            $schema = $connection->getConfig('schema') ?? 'public';
            $sql = 'SELECT COUNT(*) as count FROM information_schema.table_constraints WHERE table_schema = ? AND table_name = ? AND constraint_name = ? AND constraint_type = ?';
            $result = $connection->select($sql, [$schema, $prefix . $table, $constraintName, 'FOREIGN KEY']);
        } else {
            $sql = 'SELECT COUNT(*) as count FROM information_schema.table_constraints WHERE table_schema = ? AND table_name = ? AND constraint_name = ? AND constraint_type = ?';
            $result = $connection->select($sql, [$database, $prefix . $table, $constraintName, 'FOREIGN KEY']);
        }

        return !empty($result) && (int) $result[0]->count > 0;
    }
};



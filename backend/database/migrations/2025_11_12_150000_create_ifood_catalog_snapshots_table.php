<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ifood_catalog_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('merchant_id');
            $table->string('catalog_id')->nullable();
            $table->string('group_id')->nullable();
            $table->string('snapshot_type');
            $table->json('payload');
            $table->timestamp('captured_at')->useCurrent();
            $table->timestamps();

            $table->index(['tenant_id', 'snapshot_type']);
            $table->index(['tenant_id', 'catalog_id']);
            $table->index(['tenant_id', 'group_id']);
            $table->index('captured_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ifood_catalog_snapshots');
    }
};


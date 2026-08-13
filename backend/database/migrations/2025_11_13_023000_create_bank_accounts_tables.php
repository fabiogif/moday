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
        if (!Schema::hasTable('banks')) {
            Schema::create('banks', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('code', 10)->unique();
                $table->string('name', 100);
                $table->string('full_name', 255)->nullable();
                $table->string('ispb', 20)->nullable();
                $table->boolean('supports_pix')->default(true);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tenant_bank_accounts')) {
            Schema::create('tenant_bank_accounts', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->enum('account_type', ['checking', 'savings', 'payment']);
                $table->string('bank_code', 10);
                $table->string('bank_name', 100);
                $table->string('agency', 20);
                $table->string('agency_digit', 2)->nullable();
                $table->text('account_number');
                $table->text('account_digit');
                $table->text('account_holder_name');
                $table->text('account_holder_document');
                $table->enum('account_holder_type', ['individual', 'company']);
                $table->enum('pix_key_type', ['cpf', 'cnpj', 'email', 'phone', 'random'])->nullable();
                $table->text('pix_key')->nullable();
                $table->boolean('is_primary')->default(false);
                $table->boolean('is_active')->default(true);
                $table->boolean('is_verified')->default(false);
                $table->timestamp('verified_at')->nullable();
                $table->string('verification_method', 50)->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'is_primary'], 'tenant_bank_accounts_primary_index');
                $table->index('uuid');
            });
        }

        if (!Schema::hasTable('bank_account_logs')) {
            Schema::create('bank_account_logs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('bank_account_id')->constrained('tenant_bank_accounts')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 50);
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index('bank_account_id');
                $table->index('user_id');
                $table->index('created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_account_logs');
        Schema::dropIfExists('tenant_bank_accounts');
        Schema::dropIfExists('banks');
    }
};


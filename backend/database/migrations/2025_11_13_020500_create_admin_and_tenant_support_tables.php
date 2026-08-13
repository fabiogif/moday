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
        // Admin users table
        if (!Schema::hasTable('admin_users')) {
            Schema::create('admin_users', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->enum('role', ['super_admin', 'admin', 'analyst'])->default('analyst');
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_login_at')->nullable();
                $table->rememberToken();
                $table->softDeletes();
                $table->timestamps();

                $table->index('email');
                $table->index('role');
                $table->index('is_active');
            });
        }

        // Admin action logs
        if (!Schema::hasTable('admin_action_logs')) {
            Schema::create('admin_action_logs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('admin_user_id')->constrained('admin_users')->cascadeOnDelete();
                $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
                $table->string('action');
                $table->text('description');
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamps();

                $table->index(['admin_user_id', 'created_at'], 'admin_action_logs_admin_created_index');
                $table->index(['tenant_id', 'created_at'], 'admin_action_logs_tenant_created_index');
                $table->index('action');
            });
        }

        // Tenant metrics
        if (!Schema::hasTable('tenant_metrics')) {
            Schema::create('tenant_metrics', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->date('metric_date');
                $table->unsignedInteger('total_users')->default(0);
                $table->unsignedInteger('active_users')->default(0);
                $table->unsignedInteger('total_orders')->default(0);
                $table->decimal('total_revenue', 10, 2)->default(0);
                $table->unsignedInteger('messages_sent')->default(0);
                $table->unsignedInteger('messages_whatsapp')->default(0);
                $table->unsignedInteger('messages_email')->default(0);
                $table->unsignedInteger('messages_sms')->default(0);
                $table->unsignedInteger('login_count')->default(0);
                $table->timestamps();

                $table->unique(['tenant_id', 'metric_date'], 'tenant_metrics_tenant_date_unique');
                $table->index('metric_date');
            });
        }

        // Tenant access logs
        if (!Schema::hasTable('tenant_access_logs')) {
            Schema::create('tenant_access_logs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('logged_in_at');
                $table->timestamp('logged_out_at')->nullable();
                $table->integer('session_duration')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'logged_in_at'], 'tenant_access_logs_tenant_login_index');
                $table->index('user_id');
            });
        }

        // Tenant billing table
        if (!Schema::hasTable('tenant_billing')) {
            Schema::create('tenant_billing', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('invoice_number')->unique();
                $table->date('billing_date');
                $table->date('due_date');
                $table->decimal('amount', 10, 2);
                $table->enum('status', ['pending', 'paid', 'overdue', 'cancelled'])->default('pending');
                $table->string('plan_name')->nullable();
                $table->text('description')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->string('payment_method')->nullable();
                $table->softDeletes();
                $table->timestamps();

                $table->index(['tenant_id', 'status'], 'tenant_billing_tenant_status_index');
                $table->index('billing_date');
                $table->index('due_date');
            });
        }

        // Personal access tokens (Sanctum)
        if (!Schema::hasTable('personal_access_tokens')) {
            Schema::create('personal_access_tokens', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->morphs('tokenable');
                $table->string('name');
                $table->string('token', 64)->unique();
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }

        // Add missing columns to tenants table
        if (Schema::hasTable('tenants')) {
            Schema::table('tenants', function (Blueprint $table) {
                if (!Schema::hasColumn('tenants', 'subdomain')) {
                    $table->string('subdomain')->nullable()->unique()->after('slug');
                }
                if (!Schema::hasColumn('tenants', 'subscription_plan')) {
                    $table->string('subscription_plan')->nullable()->after('is_active');
                }
                if (!Schema::hasColumn('tenants', 'users_limit')) {
                    $table->integer('users_limit')->default(5)->after('subscription_plan');
                }
                if (!Schema::hasColumn('tenants', 'messages_limit')) {
                    $table->integer('messages_limit')->default(1000)->after('users_limit');
                }
                if (!Schema::hasColumn('tenants', 'messages_sent')) {
                    $table->integer('messages_sent')->default(0)->after('messages_limit');
                }
                if (!Schema::hasColumn('tenants', 'is_blocked')) {
                    $table->boolean('is_blocked')->default(false)->after('messages_sent');
                }
                if (!Schema::hasColumn('tenants', 'blocked_at')) {
                    $table->timestamp('blocked_at')->nullable()->after('is_blocked');
                }
                if (!Schema::hasColumn('tenants', 'blocked_reason')) {
                    $table->string('blocked_reason')->nullable()->after('blocked_at');
                }
                if (!Schema::hasColumn('tenants', 'admin_notes')) {
                    $table->text('admin_notes')->nullable()->after('blocked_reason');
                }
                if (!Schema::hasColumn('tenants', 'mrr')) {
                    $table->decimal('mrr', 10, 2)->default(0)->after('admin_notes');
                }
                if (!Schema::hasColumn('tenants', 'last_login_at')) {
                    $table->timestamp('last_login_at')->nullable()->after('mrr');
                }
                if (!Schema::hasColumn('tenants', 'total_logins')) {
                    $table->integer('total_logins')->default(0)->after('last_login_at');
                }
                if (!Schema::hasColumn('tenants', 'subscription_active')) {
                    $table->boolean('subscription_active')->default(false)->after('total_logins');
                }
                if (!Schema::hasColumn('tenants', 'subscription_suspended')) {
                    $table->boolean('subscription_suspended')->default(false)->after('subscription_active');
                }
                if (!Schema::hasColumn('tenants', 'account_status')) {
                    $table->enum('account_status', ['trial', 'active', 'expired', 'suspended'])->default('trial')->after('subscription_suspended');
                }
                if (!Schema::hasColumn('tenants', 'trial_started_at')) {
                    $table->timestamp('trial_started_at')->nullable()->after('account_status');
                }
                if (!Schema::hasColumn('tenants', 'trial_expires_at')) {
                    $table->timestamp('trial_expires_at')->nullable()->after('trial_started_at');
                }
                if (!Schema::hasColumn('tenants', 'activated_at')) {
                    $table->timestamp('activated_at')->nullable()->after('trial_expires_at');
                }
                if (!Schema::hasColumn('tenants', 'last_payment_at')) {
                    $table->timestamp('last_payment_at')->nullable()->after('activated_at');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_billing');
        Schema::dropIfExists('tenant_access_logs');
        Schema::dropIfExists('tenant_metrics');
        Schema::dropIfExists('admin_action_logs');
        Schema::dropIfExists('admin_users');
        Schema::dropIfExists('personal_access_tokens');

        if (Schema::hasTable('tenants')) {
            Schema::table('tenants', function (Blueprint $table) {
                $columns = [
                    'subdomain',
                    'users_limit',
                    'messages_limit',
                    'messages_sent',
                    'is_blocked',
                    'blocked_at',
                    'blocked_reason',
                    'admin_notes',
                    'mrr',
                    'last_login_at',
                    'total_logins',
                    'subscription_active',
                    'subscription_suspended',
                    'account_status',
                    'trial_started_at',
                    'trial_expires_at',
                    'activated_at',
                    'last_payment_at',
                ];

                foreach ($columns as $column) {
                    if (Schema::hasColumn('tenants', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};


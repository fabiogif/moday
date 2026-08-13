<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('visit_recurrences')) {
            Schema::create('visit_recurrences', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->enum('frequency', ['daily', 'weekly', 'monthly', 'custom']);
                $table->unsignedSmallInteger('interval_count')->default(1);
                $table->json('days_of_week')->nullable();
                $table->unsignedTinyInteger('day_of_month')->nullable();
                $table->time('scheduled_start_time');
                $table->time('scheduled_end_time');
                $table->enum('type', ['venda', 'pos_venda', 'cobranca', 'prospeccao', 'entrega', 'treinamento', 'suporte', 'checkin']);
                $table->enum('priority', ['baixa', 'normal', 'alta', 'urgente'])->default('normal');
                $table->date('starts_on');
                $table->date('ends_on')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['tenant_id', 'is_active']);
                $table->index(['tenant_id', 'client_id']);
                $table->index(['tenant_id', 'user_id']);
            });
        }

        if (!Schema::hasTable('visits')) {
            Schema::create('visits', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();

                $table->date('scheduled_date');
                $table->time('scheduled_start_time');
                $table->time('scheduled_end_time');
                $table->enum('type', ['venda', 'pos_venda', 'cobranca', 'prospeccao', 'entrega', 'treinamento', 'suporte', 'checkin']);
                $table->enum('priority', ['baixa', 'normal', 'alta', 'urgente'])->default('normal');
                $table->enum('status', ['agendada', 'em_andamento', 'concluida', 'cancelada', 'reagendada', 'cliente_ausente', 'sem_sucesso'])->default('agendada');
                $table->text('objective_notes')->nullable();

                $table->foreignId('recurrence_id')->nullable()->constrained('visit_recurrences')->nullOnDelete();
                $table->foreignId('rescheduled_from_visit_id')->nullable()->constrained('visits')->nullOnDelete();

                $table->timestamp('checkin_at')->nullable();
                $table->decimal('checkin_lat', 10, 7)->nullable();
                $table->decimal('checkin_lng', 10, 7)->nullable();
                $table->string('checkin_address')->nullable();
                $table->unsignedInteger('checkin_distance_meters')->nullable();
                $table->boolean('checkin_out_of_range')->default(false);

                $table->timestamp('checkout_at')->nullable();
                $table->decimal('checkout_lat', 10, 7)->nullable();
                $table->decimal('checkout_lng', 10, 7)->nullable();
                $table->string('checkout_address')->nullable();
                $table->unsignedInteger('service_duration_minutes')->nullable();

                $table->enum('result', ['venda_realizada', 'sem_interesse', 'apenas_visita', 'pendencia', 'sem_sucesso'])->nullable();
                $table->decimal('order_value', 12, 2)->nullable();
                $table->foreignId('sale_order_id')->nullable()->constrained('sale_orders')->nullOnDelete();

                $table->boolean('has_pending_issue')->default(false);
                $table->date('next_visit_suggested_at')->nullable();

                $table->string('client_request_id')->nullable();
                $table->text('notes')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->unique(['tenant_id', 'client_request_id'], 'visits_tenant_client_request_unique');
                $table->index(['tenant_id', 'user_id', 'scheduled_date'], 'visits_tenant_user_date_idx');
                $table->index(['tenant_id', 'client_id', 'scheduled_date'], 'visits_tenant_client_date_idx');
                $table->index(['tenant_id', 'status'], 'visits_tenant_status_idx');
                $table->index(['tenant_id', 'scheduled_date', 'scheduled_start_time'], 'visits_tenant_date_time_idx');
            });
        }

        if (!Schema::hasTable('visit_status_histories')) {
            Schema::create('visit_status_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('visit_id')->constrained('visits')->cascadeOnDelete();
                $table->string('from_status', 30)->nullable();
                $table->string('to_status', 30);
                $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('reason')->nullable();
                $table->timestamp('occurred_at');

                $table->index(['tenant_id', 'visit_id']);
            });
        }

        if (!Schema::hasTable('visit_media')) {
            Schema::create('visit_media', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('visit_id')->constrained('visits')->cascadeOnDelete();
                $table->enum('type', ['photo', 'document', 'audio']);
                $table->string('file_path');
                $table->string('file_name');
                $table->string('mime_type')->nullable();
                $table->unsignedInteger('size_bytes')->nullable();
                $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('created_at')->nullable();

                $table->index(['tenant_id', 'visit_id', 'type']);
            });
        }

        if (!Schema::hasTable('visit_presented_products')) {
            Schema::create('visit_presented_products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('visit_id')->constrained('visits')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->boolean('was_ordered')->default(false);
                $table->string('notes')->nullable();
                $table->timestamp('created_at')->nullable();

                $table->unique(['visit_id', 'product_id'], 'visit_presented_products_unique');
            });
        }

        if (!Schema::hasTable('visit_audit_logs')) {
            Schema::create('visit_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('visit_id')->constrained('visits')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 40);
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamp('created_at')->nullable();

                $table->index(['tenant_id', 'visit_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_audit_logs');
        Schema::dropIfExists('visit_presented_products');
        Schema::dropIfExists('visit_media');
        Schema::dropIfExists('visit_status_histories');
        Schema::dropIfExists('visits');
        Schema::dropIfExists('visit_recurrences');
    }
};

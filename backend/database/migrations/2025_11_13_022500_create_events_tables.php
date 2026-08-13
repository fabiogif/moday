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
        if (!Schema::hasTable('events')) {
            Schema::create('events', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('title');
                $table->enum('type', ['promocao', 'aviso', 'outro'])->default('outro');
                $table->string('color', 7)->default('#3b82f6');
                $table->dateTime('start_date');
                $table->integer('duration_minutes');
                $table->string('location')->nullable();
                $table->text('description');
                $table->boolean('is_active')->default(true);
                $table->boolean('notifications_sent')->default(false);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'start_date'], 'events_tenant_start_index');
                $table->index(['tenant_id', 'is_active'], 'events_tenant_active_index');
            });
        }

        if (!Schema::hasTable('event_clients')) {
            Schema::create('event_clients', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
                $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
                $table->boolean('email_sent')->default(false);
                $table->boolean('whatsapp_sent')->default(false);
                $table->boolean('sms_sent')->default(false);
                $table->timestamp('notified_at')->nullable();
                $table->timestamps();

                $table->unique(['event_id', 'client_id'], 'event_clients_unique');
                $table->index('client_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_clients');
        Schema::dropIfExists('events');
    }
};


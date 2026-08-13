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
        if (!Schema::hasTable('pdv_feedbacks')) {
            Schema::create('pdv_feedbacks', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->enum('type', ['suggestion', 'bug', 'praise', 'other'])->default('suggestion');
                $table->unsignedTinyInteger('rating')->nullable()->comment('1-5 estrelas');
                $table->text('message')->nullable();
                $table->string('quick_emoji')->nullable()->comment('Emoji de feedback rápido (😊, 😐, 😞)');
                $table->json('metadata')->nullable()->comment('Dados adicionais (versão do sistema, navegador, etc)');
                $table->enum('status', ['pending', 'reviewed', 'resolved', 'dismissed'])->default('pending');
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('review_notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'type', 'created_at'], 'pdv_feedbacks_tenant_type_created_index');
                $table->index(['tenant_id', 'status'], 'pdv_feedbacks_tenant_status_index');
                $table->index(['user_id', 'created_at'], 'pdv_feedbacks_user_created_index');
                $table->index('rating');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdv_feedbacks');
    }
};


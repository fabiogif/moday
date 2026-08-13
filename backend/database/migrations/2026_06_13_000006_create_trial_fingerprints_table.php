<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trial_fingerprints', function (Blueprint $table) {
            $table->id();
            $table->string('document', 20);   // CPF or CNPJ (digits only)
            $table->enum('doc_type', ['cpf', 'cnpj']);
            $table->unsignedBigInteger('tenant_id');
            $table->timestamp('used_at')->useCurrent();

            // One trial per document, ever
            $table->unique('document', 'uq_trial_fingerprint_doc');
            $table->index('tenant_id', 'idx_tf_tenant');

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trial_fingerprints');
    }
};

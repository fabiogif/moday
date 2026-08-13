<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remove tabelas da integração iFood descontinuada.
 * Mantém migrations históricas intactas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('ifood_events');
        Schema::dropIfExists('ifood_catalog_snapshots');
        Schema::dropIfExists('ifood_oauth_sessions');
        Schema::dropIfExists('ifood_order_status_logs');
        Schema::dropIfExists('ifood_order_items');
        Schema::dropIfExists('ifood_orders');
        Schema::dropIfExists('ifood_api_logs');
        Schema::dropIfExists('ifood_api_tokens');
    }

    public function down(): void
    {
        // Não recria as tabelas — a integração foi removida do código.
        // Restaurar via migrations históricas se necessário em ambiente controlado.
    }
};

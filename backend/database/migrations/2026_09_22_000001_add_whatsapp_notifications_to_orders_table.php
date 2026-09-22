<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Escolha do cliente (checkout do cardápio) de receber ou não as notificações
 * do pedido no WhatsApp. Default true preserva o comportamento dos pedidos do PDV.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('orders', 'whatsapp_notifications')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('whatsapp_notifications')->default(true);
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'whatsapp_notifications')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('whatsapp_notifications');
            });
        }
    }
};

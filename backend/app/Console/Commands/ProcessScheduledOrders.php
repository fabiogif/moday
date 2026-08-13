<?php

namespace App\Console\Commands;

use App\Models\SaleOrder;
use App\Notifications\ScheduledOrderReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class ProcessScheduledOrders extends Command
{
    protected $signature = 'orders:process-scheduled';
    protected $description = 'Ativa pedidos agendados cujo horário já passou';

    public function handle(): int
    {
        $now = now();

        $due = SaleOrder::query()
            ->where('is_scheduled', true)
            ->where('status', 'orcamento')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', $now)
            ->with('client', 'tenant')
            ->get();

        foreach ($due as $order) {
            $order->update([
                'is_scheduled' => false,
                'status'       => 'aprovado',
                'approved_at'  => $now,
            ]);

            // Notifica o usuário responsável pelo tenant via e-mail / in-app
            $users = $order->tenant->users()->get();
            foreach ($users as $user) {
                $user->notify(new ScheduledOrderReminderNotification($order));
            }

            $this->line("Pedido {$order->identify} ativado (tenant {$order->tenant_id})");
        }

        $this->info("Processados: {$due->count()} pedidos agendados.");

        return self::SUCCESS;
    }
}

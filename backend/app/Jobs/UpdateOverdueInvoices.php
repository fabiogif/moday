<?php

namespace App\Jobs;

use App\Models\TenantBilling;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateOverdueInvoices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 60;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Iniciando atualização de faturas vencidas');

        // Busca faturas pendentes com data de vencimento passada
        $invoices = TenantBilling::pending()
            ->where('due_date', '<', today())
            ->get();

        $updated = 0;

        foreach ($invoices as $invoice) {
            try {
                $invoice->markAsOverdue();
                $updated++;
                
                Log::info("Fatura {$invoice->invoice_number} marcada como vencida");
            } catch (\Exception $e) {
                Log::error("Erro ao atualizar fatura {$invoice->invoice_number}: " . $e->getMessage());
            }
        }

        Log::info("Atualização de faturas concluída. Total atualizado: {$updated}");
    }
}


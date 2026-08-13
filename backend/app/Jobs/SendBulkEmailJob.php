<?php

namespace App\Jobs;

use App\Mail\AdminBulkEmail;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Job para envio assíncrono de e-mails em massa do painel administrativo
 */
class SendBulkEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Número de tentativas em caso de falha
     */
    public $tries = 3;

    /**
     * Timeout em segundos
     */
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $emailTo,
        public string $subject,
        public string $message,
        public Tenant $tenant,
        public int $adminId
    ) {
        // Serializar apenas o ID do tenant para evitar problemas de serialização
        $this->tenant = $tenant;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Recarregar tenant para garantir que está atualizado
            $tenant = Tenant::find($this->tenant->id);

            if (!$tenant) {
                Log::warning('SendBulkEmailJob: Tenant não encontrado', [
                    'tenant_id' => $this->tenant->id,
                    'email_to' => $this->emailTo,
                ]);
                return;
            }

            // Enviar e-mail
            Mail::to($this->emailTo)->send(
                new AdminBulkEmail($this->subject, $this->message, $tenant)
            );

            Log::info('SendBulkEmailJob: E-mail enviado com sucesso', [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'email_to' => $this->emailTo,
                'subject' => $this->subject,
                'admin_id' => $this->adminId,
            ]);
        } catch (\Exception $e) {
            Log::error('SendBulkEmailJob: Erro ao enviar e-mail', [
                'tenant_id' => $this->tenant->id ?? null,
                'email_to' => $this->emailTo,
                'subject' => $this->subject,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-lançar exceção para que o job seja marcado como falhado
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendBulkEmailJob: Job falhou após todas as tentativas', [
            'tenant_id' => $this->tenant->id ?? null,
            'email_to' => $this->emailTo,
            'subject' => $this->subject,
            'admin_id' => $this->adminId,
            'error' => $exception->getMessage(),
        ]);
    }
}







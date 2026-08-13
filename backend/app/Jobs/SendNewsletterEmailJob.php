<?php

namespace App\Jobs;

use App\Mail\NewsletterBulkEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendNewsletterEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 120;

    public function __construct(
        public string $emailTo,
        public string $subject,
        public string $message,
        public int $adminId
    ) {}

    public function handle(): void
    {
        try {
            Mail::to($this->emailTo)->send(
                new NewsletterBulkEmail($this->subject, $this->message, $this->emailTo)
            );

            Log::info('SendNewsletterEmailJob: e-mail enviado com sucesso', [
                'email_to' => $this->emailTo,
                'subject' => $this->subject,
                'admin_id' => $this->adminId,
            ]);
        } catch (\Exception $e) {
            Log::error('SendNewsletterEmailJob: erro ao enviar e-mail', [
                'email_to' => $this->emailTo,
                'subject' => $this->subject,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}

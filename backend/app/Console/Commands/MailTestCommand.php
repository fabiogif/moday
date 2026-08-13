<?php

namespace App\Console\Commands;

use App\Mail\TestMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

class MailTestCommand extends Command
{
    protected $signature = 'mail:test
                            {email? : Endereço de destino}
                            {--queue : Enfileirar em vez de enviar imediatamente}';

    protected $description = 'Envia um e-mail de teste e exibe a configuração atual de mail/fila';

    public function handle(): int
    {
        $mailer = config('mail.default');
        $smtp = config('mail.mailers.smtp');
        $queueConnection = config('queue.default');

        $this->info('Configuração de e-mail');
        $this->table(
            ['Chave', 'Valor'],
            [
                ['MAIL_MAILER', $mailer],
                ['MAIL_HOST', $smtp['host'] ?? '-'],
                ['MAIL_PORT', (string) ($smtp['port'] ?? '-')],
                ['MAIL_ENCRYPTION', $smtp['encryption'] ?? 'null'],
                ['MAIL_FROM', config('mail.from.address').' ('.config('mail.from.name').')'],
                ['QUEUE_CONNECTION', $queueConnection],
            ]
        );

        if ($mailer === 'log') {
            $this->warn('MAIL_MAILER=log — e-mails são gravados em storage/logs, não enviados de fato.');
        }

        if ($queueConnection !== 'sync') {
            $pending = $this->countPendingJobs();
            $this->line("Jobs pendentes na fila: {$pending}");
            if ($pending > 0) {
                $this->warn('Há jobs na fila. Confirme que o worker está rodando: php artisan queue:work redis --queue=emails,default');
            }
        }

        $email = $this->argument('email') ?? $this->ask('E-mail de destino');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('E-mail inválido.');

            return Command::FAILURE;
        }

        $mailable = new TestMail(
            config('app.name'),
            $mailer,
            (string) ($smtp['host'] ?? ''),
            (int) ($smtp['port'] ?? 0),
        );

        try {
            if ($this->option('queue')) {
                Mail::to($email)->queue($mailable);
                $this->info("E-mail enfileirado para {$email}.");
            } else {
                Mail::to($email)->send($mailable);
                $this->info("E-mail enviado com sucesso para {$email}.");
            }
        } catch (\Throwable $e) {
            $this->error('Falha ao enviar: '.$e->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function countPendingJobs(): int
    {
        try {
            return Queue::size('default') + Queue::size('emails');
        } catch (\Throwable) {
            return -1;
        }
    }
}

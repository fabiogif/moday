<?php

namespace App\Services;

use App\Jobs\SendBulkEmailJob;
use App\Jobs\SendNewsletterEmailJob;
use App\Models\Tenant;
use App\Repositories\Contracts\AdminActionLogRepositoryInterface;
use App\Repositories\Contracts\AdminTenantRepositoryInterface;
use App\Services\NewsletterService;
use Illuminate\Support\Facades\Log;

readonly class AdminEmailService
{
    public function __construct(
        private AdminTenantRepositoryInterface $tenantRepository,
        private AdminActionLogRepositoryInterface $actionLogRepository,
        private NewsletterService $newsletterService
    ) {}

    public function sendBulkEmail(
        $admin,
        array $tenantIds,
        string $subject,
        string $message,
        bool $sendToTenants = true,
        bool $sendToNewsletter = false
    ): array {
        if (!$sendToTenants && !$sendToNewsletter) {
            return ['success' => false, 'reason' => 'no_audience'];
        }

        $emailsQueued = 0;
        $emailsFailed = 0;
        $tenantTotal = 0;
        $newsletterTotal = 0;

        if ($sendToTenants) {
            $tenants = $this->tenantRepository->getTenantsByIds($tenantIds);
            $tenantTotal = count($tenantIds);

            if ($tenants->isEmpty() && !$sendToNewsletter) {
                return ['success' => false, 'reason' => 'no_valid_tenants'];
            }

            foreach ($tenants as $tenant) {
                try {
                    $emailTo = $this->resolveTenantEmail($tenant);

                    if (!$emailTo) {
                        Log::warning('AdminEmailService: Nenhum e-mail disponível para tenant', [
                            'tenant_id' => $tenant->id,
                            'tenant_name' => $tenant->name,
                        ]);
                        $emailsFailed++;
                        continue;
                    }

                    $personalizedSubject = $this->replaceVariables($subject, $tenant);
                    $personalizedMessage = $this->replaceVariables($message, $tenant);

                    SendBulkEmailJob::dispatch($emailTo, $personalizedSubject, $personalizedMessage, $tenant, $admin->id)
                        ->onQueue('emails');

                    $emailsQueued++;
                } catch (\Exception $e) {
                    $emailsFailed++;
                    Log::error('AdminEmailService: Erro ao enfileirar email para tenant', [
                        'tenant_id' => $tenant->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        if ($sendToNewsletter) {
            $subscribers = $this->newsletterService->getActiveSubscribers();
            $newsletterTotal = $subscribers->count();

            if ($subscribers->isEmpty() && !$sendToTenants) {
                return ['success' => false, 'reason' => 'no_subscribers'];
            }

            foreach ($subscribers as $subscriber) {
                try {
                    SendNewsletterEmailJob::dispatch(
                        $subscriber->email,
                        $subject,
                        $message,
                        $admin->id
                    )->onQueue('emails');

                    $emailsQueued++;
                } catch (\Exception $e) {
                    $emailsFailed++;
                    Log::error('AdminEmailService: Erro ao enfileirar email para inscrito', [
                        'email' => $subscriber->email,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        if ($emailsQueued === 0) {
            return ['success' => false, 'reason' => 'nothing_queued'];
        }

        $audienceParts = [];
        if ($sendToTenants && $tenantTotal > 0) {
            $audienceParts[] = "{$tenantTotal} empresa(s)";
        }
        if ($sendToNewsletter && $newsletterTotal > 0) {
            $audienceParts[] = "{$newsletterTotal} inscrito(s)";
        }

        $this->actionLogRepository->logAction(
            $admin,
            'email.bulk_send',
            "Enviou email '{$subject}' para ".implode(' e ', $audienceParts),
            null,
            null,
            [
                'subject' => $subject,
                'send_to_tenants' => $sendToTenants,
                'send_to_newsletter' => $sendToNewsletter,
                'tenant_recipients' => $tenantTotal,
                'newsletter_recipients' => $newsletterTotal,
                'queued' => $emailsQueued,
                'failed' => $emailsFailed,
            ]
        );

        return [
            'success' => true,
            'queued' => $emailsQueued,
            'failed' => $emailsFailed,
            'total' => $tenantTotal + $newsletterTotal,
            'tenant_total' => $tenantTotal,
            'newsletter_total' => $newsletterTotal,
        ];
    }

    public function getNewsletterSubscribers(): mixed
    {
        return $this->newsletterService->getActiveSubscribers()->map(function ($subscriber) {
            return [
                'id' => $subscriber->id,
                'email' => $subscriber->email,
                'subscribed_at' => $subscriber->subscribed_at?->toIso8601String(),
            ];
        });
    }

    public function getNewsletterStats(): array
    {
        return $this->newsletterService->getStats();
    }

    public function getEmailHistory(int $limit = 50): mixed
    {
        return $this->actionLogRepository->getEmailHistory($limit)
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'subject' => $log->new_values['subject'] ?? 'N/A',
                    'queued' => $log->new_values['queued'] ?? $log->new_values['sent'] ?? 0,
                    'failed' => $log->new_values['failed'] ?? 0,
                    'total' => $log->new_values['total_recipients'] ?? 0,
                    'admin' => $log->adminUser ? [
                        'name' => $log->adminUser->name,
                        'email' => $log->adminUser->email,
                    ] : null,
                    'sent_at' => $log->created_at->toIso8601String(),
                ];
            });
    }

    private function resolveTenantEmail(Tenant $tenant): ?string
    {
        if ($tenant->email) {
            return $tenant->email;
        }

        $adminUser = $tenant->users()->whereHas('profiles', function ($query) {
            $query->where('name', 'Admin');
        })->first();

        return $adminUser?->email;
    }

    private function replaceVariables(string $text, Tenant $tenant): string
    {
        $variables = [
            '{name}' => $tenant->name,
            '{subdomain}' => $tenant->subdomain,
            '{plan}' => $tenant->subscription_plan ?? 'Trial',
            '{mrr}' => number_format($tenant->mrr, 2, ',', '.'),
            '{expires_at}' => $tenant->trial_expires_at
                ? $tenant->trial_expires_at->format('d/m/Y')
                : '-',
            '{created_at}' => $tenant->created_at->format('d/m/Y'),
        ];

        return str_replace(array_keys($variables), array_values($variables), $text);
    }
}

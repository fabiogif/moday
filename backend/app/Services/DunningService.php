<?php

namespace App\Services;

use App\Models\SubscriptionDunningEvent;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

/**
 * Dunning rule engine.
 *
 * Schedule: runs daily via ProcessDunningRules job.
 *
 * D0  → email + in_app notification
 * D3  → email notification
 * D5  → email notification
 * D7  → delinquent + access blocked
 * D30 → suspended
 * D90 → anonymize personal data (LGPD)
 * D120→ delete tenant data
 */
class DunningService
{
    private const DUNNING_DAYS = [0, 3, 5, 7, 30, 90, 120];

    public function __construct(
        private readonly SubscriptionAuditService $audit,
    ) {}

    /**
     * Process dunning for a single tenant.
     * Called by the daily job for each tenant in dunning.
     */
    public function processTenant(Tenant $tenant): void
    {
        if (!$this->isDunningEligible($tenant)) {
            return;
        }

        $daysElapsed = $tenant->dunningDaysElapsed();
        $currentDay  = $tenant->dunning_day ?? 0;

        foreach (self::DUNNING_DAYS as $triggerDay) {
            // Only process each dunning day once and in order
            if ($daysElapsed >= $triggerDay && $currentDay < $triggerDay) {
                $this->processDunningDay($tenant, $triggerDay);
                // Update dunning_day to latest processed
                $tenant->dunning_day = $triggerDay;
                $tenant->save();
            }
        }
    }

    /**
     * Process all dunning-eligible tenants.
     * Returns count of tenants processed.
     */
    public function processAll(): int
    {
        $tenants = Tenant::inDunning()->get();
        $count   = 0;

        foreach ($tenants as $tenant) {
            try {
                $this->processTenant($tenant);
                $count++;
            } catch (\Throwable $e) {
                Log::error('DunningService: erro ao processar tenant', [
                    'tenant_id' => $tenant->id,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    // ── Day-specific actions ──────────────────────────────────────────────────

    private function processDunningDay(Tenant $tenant, int $day): void
    {
        Log::info("DunningService: processando D{$day}", ['tenant_id' => $tenant->id]);

        match ($day) {
            0   => $this->handleD0($tenant),
            3   => $this->handleD3($tenant),
            5   => $this->handleD5($tenant),
            7   => $this->handleD7($tenant),
            30  => $this->handleD30($tenant),
            90  => $this->handleD90($tenant),
            120 => $this->handleD120($tenant),
            default => null,
        };
    }

    private function handleD0(Tenant $tenant): void
    {
        $this->sendEmail($tenant, 0, 'Pagamento pendente - ação necessária');
        $this->sendInApp($tenant, 0, 'Seu pagamento está pendente. Regularize para continuar usando o sistema.');
        $this->audit->dunningNotification($tenant, 0, 'email');
        $this->audit->dunningNotification($tenant, 0, 'in_app');
    }

    private function handleD3(Tenant $tenant): void
    {
        $this->sendEmail($tenant, 3, '3 dias de pagamento pendente - evite bloqueio');
        $this->audit->dunningNotification($tenant, 3, 'email');
    }

    private function handleD5(Tenant $tenant): void
    {
        $this->sendEmail($tenant, 5, 'Último aviso - seu acesso será bloqueado em 2 dias');
        $this->audit->dunningNotification($tenant, 5, 'email');
    }

    private function handleD7(Tenant $tenant): void
    {
        DB::transaction(function () use ($tenant) {
            $tenant->markDelinquent();
            $this->audit->delinquent($tenant);
            $this->recordDunningEvent($tenant, 7, 'status_change');
        });

        $this->sendEmail($tenant, 7, 'Acesso bloqueado por inadimplência');
        $this->audit->dunningNotification($tenant, 7, 'email');

        Log::warning('DunningService: tenant marcado como delinquente', ['tenant_id' => $tenant->id]);
    }

    private function handleD30(Tenant $tenant): void
    {
        DB::transaction(function () use ($tenant) {
            $tenant->suspend();
            $this->audit->suspended($tenant);
            $this->recordDunningEvent($tenant, 30, 'status_change');
        });

        $this->sendEmail($tenant, 30, 'Assinatura suspensa - dados serão removidos em 60 dias');
        $this->audit->dunningNotification($tenant, 30, 'email');

        Log::warning('DunningService: tenant suspenso (D30)', ['tenant_id' => $tenant->id]);
    }

    private function handleD90(Tenant $tenant): void
    {
        DB::transaction(function () use ($tenant) {
            $this->anonymizePersonalData($tenant);
            $this->recordDunningEvent($tenant, 90, 'status_change');
            $this->audit->log($tenant, 'data_anonymized', metadata: ['lgpd' => true]);
        });

        Log::warning('DunningService: dados anonimizados (D90 LGPD)', ['tenant_id' => $tenant->id]);
    }

    private function handleD120(Tenant $tenant): void
    {
        DB::transaction(function () use ($tenant) {
            $this->scheduleDeletion($tenant);
            $this->recordDunningEvent($tenant, 120, 'status_change');
            $this->audit->log($tenant, 'data_deletion_scheduled');
        });

        Log::warning('DunningService: deleção agendada (D120)', ['tenant_id' => $tenant->id]);
    }

    // ── LGPD compliance ───────────────────────────────────────────────────────

    private function anonymizePersonalData(Tenant $tenant): void
    {
        $anonId = 'ANON-' . $tenant->id;

        $tenant->update([
            'name'    => $anonId,
            'email'   => "{$anonId}@deleted.invalid",
            'cnpj'    => null,
            'phone'   => null,
            'address' => null,
            'city'    => null,
            'state'   => null,
            'zipcode' => null,
        ]);

        // Anonymize users
        $tenant->users()->update([
            'name'  => $anonId,
            'email' => DB::raw("CONCAT('{$anonId}-', id, '@deleted.invalid')"),
            'phone' => null,
        ]);
    }

    private function scheduleDeletion(Tenant $tenant): void
    {
        $tenant->data_deletion_scheduled_at = now()->addDays(7);
        $tenant->save();
    }

    // ── Notification helpers ──────────────────────────────────────────────────

    private function sendEmail(Tenant $tenant, int $day, string $subject): void
    {
        if ($this->wasSent($tenant, $day, 'email')) {
            return;
        }

        try {
            // Events/listeners handle the actual email — we just fire the notification
            // via a simple mailable. Using Laravel Notification for flexibility.
            $adminEmail = $tenant->email;

            if ($adminEmail) {
                Mail::raw(
                    $this->emailBody($tenant, $day),
                    function ($message) use ($adminEmail, $subject) {
                        $message->to($adminEmail)->subject($subject);
                    }
                );
            }

            $this->recordDunningEvent($tenant, $day, 'email');
        } catch (\Throwable $e) {
            Log::error("DunningService: falha ao enviar email D{$day}", [
                'tenant_id' => $tenant->id,
                'error'     => $e->getMessage(),
            ]);
            $this->recordDunningEvent($tenant, $day, 'email', success: false, error: $e->getMessage());
        }
    }

    private function sendInApp(Tenant $tenant, int $day, string $message): void
    {
        if ($this->wasSent($tenant, $day, 'in_app')) {
            return;
        }

        try {
            // Dispatch in-app notification via the NotificationService / DB channel
            $adminUser = $tenant->users()->first();

            if ($adminUser) {
                \App\Models\Notification::create([
                    'user_id'    => $adminUser->id,
                    'tenant_id'  => $tenant->id,
                    'type'       => 'dunning',
                    'title'      => 'Pagamento pendente',
                    'message'    => $message,
                    'read'       => false,
                ]);
            }

            $this->recordDunningEvent($tenant, $day, 'in_app');
        } catch (\Throwable $e) {
            Log::error("DunningService: falha ao enviar in_app D{$day}", [
                'tenant_id' => $tenant->id,
                'error'     => $e->getMessage(),
            ]);
            $this->recordDunningEvent($tenant, $day, 'in_app', success: false, error: $e->getMessage());
        }
    }

    private function emailBody(Tenant $tenant, int $day): string
    {
        $daysLeft = 7 - $day;

        return match (true) {
            $day === 0  => "Olá, {$tenant->name}!\n\nSeu pagamento está pendente. Por favor, regularize sua situação para continuar usando o DistribTec.\n\nAcesse o painel e atualize suas informações de pagamento.",
            $day === 3  => "Olá, {$tenant->name}!\n\nJá se passaram 3 dias desde que seu pagamento ficou pendente.\n\nRegularize agora para evitar o bloqueio do acesso em " . ($daysLeft) . " dias.",
            $day === 5  => "Olá, {$tenant->name}!\n\nÚltimo aviso! Seu acesso será bloqueado em 2 dias se o pagamento não for regularizado.\n\nAcesse o painel agora e atualize seu cartão de crédito.",
            $day === 7  => "Olá, {$tenant->name}!\n\nSeu acesso ao DistribTec foi bloqueado por inadimplência.\n\nPara reativar, acesse o painel e regularize seu pagamento.",
            $day === 30 => "Olá, {$tenant->name}!\n\nSua assinatura foi suspensa. Seus dados serão removidos em 60 dias se não houver regularização.\n\nEntre em contato com nosso suporte para recuperar o acesso.",
            default     => "Olá, {$tenant->name}!\n\nAtualização sobre sua conta DistribTec.",
        };
    }

    // ── Idempotency helpers ───────────────────────────────────────────────────

    private function wasSent(Tenant $tenant, int $day, string $channel): bool
    {
        return SubscriptionDunningEvent::where('tenant_id', $tenant->id)
            ->where('dunning_day', $day)
            ->where('channel', $channel)
            ->where('success', true)
            ->exists();
    }

    private function recordDunningEvent(
        Tenant $tenant,
        int    $day,
        string $channel,
        bool   $success = true,
        string $error   = ''
    ): void {
        try {
            SubscriptionDunningEvent::firstOrCreate(
                [
                    'tenant_id'  => $tenant->id,
                    'dunning_day' => $day,
                    'channel'    => $channel,
                ],
                [
                    'success'       => $success,
                    'error_message' => $error ?: null,
                    'sent_at'       => now(),
                ]
            );
        } catch (\Throwable $e) {
            // UNIQUE constraint race — already recorded, ignore
        }
    }

    // ── Eligibility check ─────────────────────────────────────────────────────

    private function isDunningEligible(Tenant $tenant): bool
    {
        return $tenant->dunning_started_at !== null
            && in_array($tenant->account_status, ['pending', 'under_review', 'delinquent', 'suspended'], true);
    }
}

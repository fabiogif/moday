<?php

namespace App\Services;

use App\Models\Tenant;
use App\Repositories\Contracts\AdminActionLogRepositoryInterface;
use App\Repositories\Contracts\AdminTenantRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

readonly class AdminTenantService
{
    public function __construct(
        private AdminTenantRepositoryInterface $tenantRepository,
        private AdminActionLogRepositoryInterface $actionLogRepository
    ) {}

    public function listTenants(array $filters, int $perPage = 15): array
    {
        $tenants = $this->tenantRepository->paginateWithFilters($filters, $perPage);

        return [
            'data' => $tenants->getCollection()->map(fn (Tenant $tenant) => $this->formatTenantListItem($tenant)),
            'meta' => [
                'current_page' => $tenants->currentPage(),
                'last_page' => $tenants->lastPage(),
                'per_page' => $tenants->perPage(),
                'total' => $tenants->total(),
            ],
        ];
    }

    public function getTenantDetails(int $id): array
    {
        $tenant = $this->tenantRepository->findOrFail($id);
        $metrics30Days = $this->tenantRepository->getMetricsForPeriod($id, now()->subDays(30), now());
        $billing = $this->tenantRepository->getBillingHistory($id);
        $recentAccess = $this->tenantRepository->getRecentAccess($id);
        $actionHistory = $this->tenantRepository->getActionHistory($id);
        $owner = $this->tenantRepository->findOwnerUser($id);

        return [
            'tenant' => $this->formatTenantDetail($tenant),
            'owner' => $owner ? [
                'id' => $owner->id,
                'name' => $owner->name,
                'email' => $owner->email,
            ] : null,
            'metrics' => [
                'total_orders' => $metrics30Days->sum('total_orders'),
                'total_revenue' => $metrics30Days->sum('total_revenue'),
                'total_messages' => $metrics30Days->sum('messages_sent'),
                'total_logins' => $metrics30Days->sum('login_count'),
                'daily_data' => $metrics30Days->map(fn ($m) => [
                    'date' => $m->metric_date->format('Y-m-d'),
                    'orders' => $m->total_orders,
                    'revenue' => $m->total_revenue,
                    'messages' => $m->messages_sent,
                    'logins' => $m->login_count,
                ]),
            ],
            'billing' => $billing->map(fn ($b) => [
                'id' => $b->id,
                'invoice_number' => $b->invoice_number,
                'amount' => $b->amount,
                'status' => $b->status,
                'billing_date' => $b->billing_date->format('Y-m-d'),
                'due_date' => $b->due_date->format('Y-m-d'),
                'paid_at' => $b->paid_at?->format('Y-m-d'),
            ]),
            'recent_access' => $recentAccess->map(fn ($log) => [
                'user' => $log->user?->name,
                'logged_in_at' => $log->logged_in_at->toIso8601String(),
                'session_duration' => $log->session_duration,
                'ip_address' => $log->ip_address,
            ]),
            'action_history' => $actionHistory->map(fn ($log) => [
                'action' => $log->action,
                'description' => $log->description,
                'admin' => $log->adminUser?->name,
                'created_at' => $log->created_at->toIso8601String(),
            ]),
        ];
    }

    public function updateTenant($admin, int $id, array $data): Tenant
    {
        $tenant = $this->tenantRepository->findOrFail($id);
        $oldValues = $tenant->only(['admin_notes', 'users_limit', 'messages_limit']);
        $tenant = $this->tenantRepository->updateTenant($tenant, $data);

        $this->actionLogRepository->logAction(
            $admin,
            'tenant.update',
            "Atualizou informações da empresa {$tenant->name}",
            $tenant,
            $oldValues,
            $tenant->only(['admin_notes', 'users_limit', 'messages_limit'])
        );

        return $tenant;
    }

    public function updateOwnerCredentials($admin, int $id, array $data): array
    {
        $tenant = $this->tenantRepository->findOrFail($id);
        $owner = $this->tenantRepository->findOwnerUser($id);

        if (!$owner) {
            throw ValidationException::withMessages([
                'email' => 'Nenhum usuário responsável encontrado para esta empresa.',
            ]);
        }

        if ($this->tenantRepository->emailExistsForTenant($id, $data['email'], $owner->id)) {
            throw ValidationException::withMessages([
                'email' => 'Este e-mail já está em uso por outro usuário desta empresa.',
            ]);
        }

        $oldEmail = $owner->email;
        $passwordChanged = !empty($data['password']);

        $updatePayload = ['email' => $data['email']];
        if ($passwordChanged) {
            $updatePayload['password'] = $data['password'];
        }

        $owner = $this->tenantRepository->updateOwnerUser($owner, $updatePayload);

        $this->actionLogRepository->logAction(
            $admin,
            'tenant.update_owner_credentials',
            "Atualizou credenciais do usuário responsável ({$owner->name}) da empresa {$tenant->name}"
                . ($passwordChanged ? ' — e-mail e senha alterados' : ' — e-mail alterado'),
            $tenant,
            ['email' => $oldEmail],
            ['email' => $owner->email, 'password_changed' => $passwordChanged]
        );

        return [
            'id' => $owner->id,
            'name' => $owner->name,
            'email' => $owner->email,
        ];
    }

    public function activateTenant($admin, int $id): void
    {
        $tenant = $this->tenantRepository->findOrFail($id);
        $oldStatus = $tenant->account_status;
        $this->tenantRepository->updateTenant($tenant, [
            'account_status' => 'active',
            'activated_at' => now(),
        ]);

        $this->actionLogRepository->logAction(
            $admin,
            'tenant.activate',
            "Ativou a empresa {$tenant->name}",
            $tenant,
            ['account_status' => $oldStatus],
            ['account_status' => 'active']
        );
    }

    public function suspendTenant($admin, int $id, string $reason): void
    {
        $tenant = $this->tenantRepository->findOrFail($id);
        $oldStatus = $tenant->account_status;
        $this->tenantRepository->updateTenant($tenant, [
            'account_status' => 'suspended',
            'admin_notes' => ($tenant->admin_notes ? $tenant->admin_notes . "\n\n" : '')
                . 'Suspenso em ' . now()->format('d/m/Y H:i') . ': ' . $reason,
        ]);

        $this->actionLogRepository->logAction(
            $admin,
            'tenant.suspend',
            "Suspendeu a empresa {$tenant->name}. Motivo: {$reason}",
            $tenant,
            ['account_status' => $oldStatus],
            ['account_status' => 'suspended', 'reason' => $reason]
        );
    }

    public function blockTenant($admin, int $id, string $reason): void
    {
        $tenant = $this->tenantRepository->findOrFail($id);
        $this->tenantRepository->updateTenant($tenant, [
            'is_blocked' => true,
            'blocked_at' => now(),
            'blocked_reason' => $reason,
        ]);

        $this->actionLogRepository->logAction(
            $admin,
            'tenant.block',
            "Bloqueou a empresa {$tenant->name}. Motivo: {$reason}",
            $tenant,
            ['is_blocked' => false],
            ['is_blocked' => true, 'reason' => $reason]
        );
    }

    public function unblockTenant($admin, int $id): void
    {
        $tenant = $this->tenantRepository->findOrFail($id);
        $this->tenantRepository->updateTenant($tenant, [
            'is_blocked' => false,
            'blocked_at' => null,
            'blocked_reason' => null,
        ]);

        $this->actionLogRepository->logAction(
            $admin,
            'tenant.unblock',
            "Desbloqueou a empresa {$tenant->name}",
            $tenant,
            ['is_blocked' => true],
            ['is_blocked' => false]
        );
    }

    public function updatePlan($admin, int $id, string $plan, float $mrr): void
    {
        $tenant = $this->tenantRepository->findOrFail($id);
        $oldPlan = $tenant->subscription_plan;
        $oldMrr = $tenant->mrr;

        $this->tenantRepository->updateTenant($tenant, [
            'subscription_plan' => $plan,
            'mrr' => $mrr,
        ]);

        $this->actionLogRepository->logAction(
            $admin,
            'tenant.update_plan',
            "Alterou o plano da empresa {$tenant->name} de {$oldPlan} para {$plan}",
            $tenant,
            ['subscription_plan' => $oldPlan, 'mrr' => $oldMrr],
            ['subscription_plan' => $plan, 'mrr' => $mrr]
        );
    }

    public function pauseAccess($admin, int $id, string $reason): void
    {
        $tenant = $this->tenantRepository->findOrFail($id);
        $oldStatus = $tenant->account_status;

        $this->tenantRepository->updateTenant($tenant, [
            'account_status' => 'suspended',
            'is_blocked' => true,
            'blocked_at' => now(),
            'blocked_reason' => $reason,
            'admin_notes' => ($tenant->admin_notes ? $tenant->admin_notes . "\n\n" : '')
                . 'Acesso pausado em ' . now()->format('d/m/Y H:i') . ': ' . $reason,
        ]);

        $this->actionLogRepository->logAction(
            $admin,
            'tenant.pause_access',
            "Pausou o acesso da empresa {$tenant->name}. Motivo: {$reason}",
            $tenant,
            ['account_status' => $oldStatus, 'is_blocked' => false],
            ['account_status' => 'suspended', 'is_blocked' => true, 'reason' => $reason]
        );
    }

    public function restoreAccess($admin, int $id): void
    {
        $tenant = $this->tenantRepository->findOrFail($id);
        $oldStatus = $tenant->account_status;

        $this->tenantRepository->updateTenant($tenant, [
            'is_blocked' => false,
            'blocked_at' => null,
            'blocked_reason' => null,
        ]);

        $this->actionLogRepository->logAction(
            $admin,
            'tenant.restore_access',
            "Restaurou o acesso da empresa {$tenant->name}",
            $tenant,
            ['is_blocked' => true, 'account_status' => $oldStatus],
            ['is_blocked' => false]
        );
    }

    public function deleteTenant($admin, int $id): string
    {
        $tenant = $this->tenantRepository->findOrFail($id);
        $tenantName = $tenant->name;
        $tenantData = [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'subdomain' => $tenant->subdomain,
            'account_status' => $tenant->account_status,
        ];

        $this->tenantRepository->softDelete($tenant);

        $this->actionLogRepository->logAction(
            $admin,
            'tenant.delete',
            "Excluiu a empresa {$tenantName}",
            null,
            $tenantData,
            ['deleted_at' => now()->toIso8601String()]
        );

        return $tenantName;
    }

    public function forceDeleteTenant($admin, int $id): string
    {
        $tenant = $this->tenantRepository->findWithTrashedOrFail($id);
        $tenantName = $tenant->name;

        $this->tenantRepository->forceDelete($tenant);
        $this->actionLogRepository->createForceDeleteLog($admin, $tenantName);

        return $tenantName;
    }

    private function formatTenantListItem(Tenant $tenant): array
    {
        return [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'subdomain' => $tenant->subdomain,
            'account_status' => $tenant->account_status,
            'subscription_plan' => $tenant->subscription_plan,
            'plan_id' => $tenant->plan_id,
            'is_blocked' => $tenant->is_blocked,
            'mrr' => $tenant->mrr,
            'users_limit' => $tenant->users_limit,
            'messages_limit' => $tenant->messages_limit,
            'last_login_at' => $tenant->last_login_at?->toIso8601String(),
            'created_at' => $tenant->created_at->toIso8601String(),
            'trial_expires_at' => $tenant->trial_expires_at?->toIso8601String(),
        ];
    }

    private function formatTenantDetail(Tenant $tenant): array
    {
        return [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'subdomain' => $tenant->subdomain,
            'account_status' => $tenant->account_status,
            'subscription_plan' => $tenant->subscription_plan,
            'plan_id' => $tenant->plan_id,
            'is_blocked' => $tenant->is_blocked,
            'blocked_at' => $tenant->blocked_at?->toIso8601String(),
            'blocked_reason' => $tenant->blocked_reason,
            'admin_notes' => $tenant->admin_notes,
            'mrr' => $tenant->mrr,
            'users_limit' => $tenant->users_limit,
            'messages_limit' => $tenant->messages_limit,
            'total_logins' => $tenant->total_logins,
            'last_login_at' => $tenant->last_login_at?->toIso8601String(),
            'trial_started_at' => $tenant->trial_started_at?->toIso8601String(),
            'trial_expires_at' => $tenant->trial_expires_at?->toIso8601String(),
            'activated_at' => $tenant->activated_at?->toIso8601String(),
            'created_at' => $tenant->created_at->toIso8601String(),
        ];
    }
}

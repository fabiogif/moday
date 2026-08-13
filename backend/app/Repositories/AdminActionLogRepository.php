<?php

namespace App\Repositories;

use App\Models\AdminActionLog;
use App\Repositories\Contracts\AdminActionLogRepositoryInterface;

class AdminActionLogRepository implements AdminActionLogRepositoryInterface
{
    public function logAction(
        $admin,
        string $action,
        string $description,
        $tenant = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        AdminActionLog::log($admin, $action, $description, $tenant, $oldValues, $newValues);
    }

    public function createForceDeleteLog($admin, string $tenantName): void
    {
        AdminActionLog::create([
            'admin_user_id' => $admin->id,
            'tenant_id' => null,
            'action' => 'tenant.force_delete',
            'description' => "Excluiu PERMANENTEMENTE a empresa {$tenantName}",
            'old_values' => ['name' => $tenantName],
            'new_values' => ['permanently_deleted' => true],
            'ip_address' => request()->ip(),
        ]);
    }

    public function getEmailHistory(int $limit = 50): mixed
    {
        return AdminActionLog::where('action', 'email.bulk_send')
            ->with('adminUser:id,name,email')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}

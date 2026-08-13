<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditService
{
    public function log(
        int $tenantId,
        ?int $userId,
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?array $payload = null,
        ?Request $request = null,
    ): AuditLog {
        return AuditLog::create([
            'tenant_id'   => $tenantId,
            'user_id'     => $userId,
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'payload'     => $payload,
            'ip_address'  => $request?->ip(),
            'user_agent'  => $request?->userAgent(),
        ]);
    }

    public function logChange(
        int $tenantId,
        ?int $userId,
        string $action,
        string $entityType,
        ?int $entityId,
        ?array $oldValues,
        ?array $newValues,
        ?array $metadata = null,
        ?Request $request = null,
    ): AuditLog {
        return AuditLog::create([
            'tenant_id'   => $tenantId,
            'user_id'     => $userId,
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'old_values'  => $oldValues,
            'new_values'  => $newValues,
            'metadata'    => $metadata,
            'ip_address'  => $request?->ip(),
            'user_agent'  => $request?->userAgent(),
        ]);
    }

    public function list(int $tenantId, array $filters = [], int $perPage = 50): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = AuditLog::forTenant($tenantId)
            ->with('user:id,name')
            ->latest();

        if (!empty($filters['entity_type'])) {
            $query->where('entity_type', $filters['entity_type']);
        }
        if (!empty($filters['entity_id'])) {
            $query->where('entity_id', (int) $filters['entity_id']);
        }
        if (!empty($filters['action'])) {
            $query->where('action', 'like', "%{$filters['action']}%");
        }
        if (!empty($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage);
    }
}

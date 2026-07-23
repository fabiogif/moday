<?php

namespace App\Services\Visit;

use App\Models\Visit;
use App\Models\VisitAuditLog;
use Illuminate\Support\Facades\Request as RequestFacade;

class VisitAuditService
{
    public function log(Visit $visit, string $action, ?int $userId, ?array $oldValues = null, ?array $newValues = null): VisitAuditLog
    {
        return VisitAuditLog::create([
            'tenant_id' => $visit->tenant_id,
            'visit_id' => $visit->id,
            'user_id' => $userId,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => RequestFacade::ip(),
            'created_at' => now(),
        ]);
    }
}

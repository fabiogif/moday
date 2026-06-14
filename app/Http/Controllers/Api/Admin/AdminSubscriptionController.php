<?php

namespace App\Http\Controllers\Api\Admin;

use App\Classes\ApiResponseClass;
use App\Models\Plan;
use App\Models\SubscriptionEvent;
use App\Models\Tenant;
use App\Services\SubscriptionAuditService;
use App\Services\SubscriptionDomainService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminSubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionDomainService $domain,
        private readonly SubscriptionAuditService  $audit,
    ) {}

    /** List tenants in dunning with current dunning state. */
    public function dunningList(Request $request): JsonResponse
    {
        $tenants = Tenant::inDunning()
            ->with('plan')
            ->orderByDesc('dunning_started_at')
            ->paginate(20);

        return ApiResponseClass::sendResponse($tenants, 'Tenants em cobrança recuperados');
    }

    /** List tenants with cancellation pending. */
    public function cancellationPendingList(Request $request): JsonResponse
    {
        $tenants = Tenant::whereNotNull('cancellation_requested_at')
            ->whereNull('cancelled_at')
            ->with('plan')
            ->orderByDesc('cancellation_requested_at')
            ->paginate(20);

        return ApiResponseClass::sendResponse($tenants, 'Tenants com cancelamento pendente');
    }

    /** Get full subscription audit log for a tenant. */
    public function auditLog(Request $request, int $tenantId): JsonResponse
    {
        $tenant = Tenant::findOrFail($tenantId);

        $events = SubscriptionEvent::where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->paginate(30);

        return ApiResponseClass::sendResponse([
            'tenant' => $tenant->only(['id', 'name', 'account_status', 'plan_id']),
            'events' => $events,
        ], 'Auditoria recuperada');
    }

    /** Admin override — force a status change with reason. */
    public function overrideStatus(Request $request, int $tenantId): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:trial,active,pending,under_review,delinquent,suspended,cancelled,expired',
            'reason' => 'required|string|max:500',
        ]);

        $tenant   = Tenant::findOrFail($tenantId);
        $admin    = Auth::user();
        $newStatus = $request->input('status');
        $oldStatus = $tenant->account_status;

        try {
            $tenant->account_status = $newStatus;
            $tenant->is_blocked     = in_array($newStatus, ['delinquent', 'suspended'], true);
            $tenant->save();

            $this->audit->adminOverride(
                $tenant,
                $oldStatus,
                $newStatus,
                $admin->id ?? 0,
                $request->input('reason')
            );

            return ApiResponseClass::sendResponse([
                'tenant'  => $tenant->fresh(),
                'message' => "Status alterado de '{$oldStatus}' para '{$newStatus}'.",
            ], 'Status alterado com sucesso');
        } catch (\Throwable $e) {
            Log::error('AdminSubscription: erro ao alterar status', [
                'tenant_id' => $tenantId,
                'error'     => $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /** Admin: manually trigger cancellation finalization. */
    public function finalizeCancellation(Request $request, int $tenantId): JsonResponse
    {
        $tenant = Tenant::findOrFail($tenantId);

        if (!$tenant->hasCancellationPending()) {
            return response()->json(['success' => false, 'message' => 'Nenhum cancelamento pendente.'], 422);
        }

        $this->domain->finalizeCancellation($tenant);

        return ApiResponseClass::sendResponse(null, 'Cancelamento finalizado com sucesso');
    }

    /** Admin: manually trigger dunning for a tenant (for testing). */
    public function triggerDunning(Request $request, int $tenantId): JsonResponse
    {
        $tenant = Tenant::findOrFail($tenantId);

        if (!$tenant->isInDunning()) {
            $tenant->markPending();
        }

        $dunning = app(\App\Services\DunningService::class);
        $dunning->processTenant($tenant->fresh());

        return ApiResponseClass::sendResponse(null, 'Dunning processado');
    }

    /** Subscription overview stats for admin dashboard. */
    public function stats(Request $request): JsonResponse
    {
        $stats = [
            'total_active'              => Tenant::where('account_status', 'active')->count(),
            'total_trial'               => Tenant::where('account_status', 'trial')->count(),
            'total_pending'             => Tenant::where('account_status', 'pending')->count(),
            'total_delinquent'          => Tenant::where('account_status', 'delinquent')->count(),
            'total_suspended'           => Tenant::where('account_status', 'suspended')->count(),
            'total_cancelled'           => Tenant::where('account_status', 'cancelled')->count(),
            'total_in_dunning'          => Tenant::inDunning()->count(),
            'total_cancellation_pending'=> Tenant::whereNotNull('cancellation_requested_at')->whereNull('cancelled_at')->count(),
            'total_downgrade_scheduled' => Tenant::whereNotNull('scheduled_downgrade_plan_id')->count(),
        ];

        return ApiResponseClass::sendResponse($stats, 'Estatísticas recuperadas');
    }
}

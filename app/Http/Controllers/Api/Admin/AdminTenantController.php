<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminTenantService;
use Illuminate\Http\Request;

class AdminTenantController extends Controller
{
    public function __construct(
        private readonly AdminTenantService $tenantService
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only(['status', 'plan', 'blocked', 'search', 'sort_by', 'sort_direction']);
        $perPage = (int) $request->input('per_page', 15);
        $result = $this->tenantService->listTenants($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'meta' => $result['meta'],
        ]);
    }

    public function show($id)
    {
        return response()->json([
            'success' => true,
            'data' => $this->tenantService->getTenantDetails((int) $id),
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'admin_notes' => 'nullable|string',
            'users_limit' => 'nullable|integer|min:1',
            'messages_limit' => 'nullable|integer|min:0',
        ]);

        $tenant = $this->tenantService->updateTenant(
            auth()->guard('admin')->user(),
            (int) $id,
            $request->only(['admin_notes', 'users_limit', 'messages_limit'])
        );

        return response()->json([
            'success' => true,
            'message' => 'Empresa atualizada com sucesso.',
            'data' => $tenant,
        ]);
    }

    public function updateOwnerCredentials(Request $request, $id)
    {
        $request->validate([
            'email' => 'required|email|max:255',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $owner = $this->tenantService->updateOwnerCredentials(
            auth()->guard('admin')->user(),
            (int) $id,
            $request->only(['email', 'password'])
        );

        return response()->json([
            'success' => true,
            'message' => 'Credenciais do usuário atualizadas com sucesso.',
            'data' => $owner,
        ]);
    }

    public function activate($id)
    {
        $this->tenantService->activateTenant(auth()->guard('admin')->user(), (int) $id);

        return response()->json([
            'success' => true,
            'message' => 'Empresa ativada com sucesso.',
        ]);
    }

    public function suspend(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $this->tenantService->suspendTenant(
            auth()->guard('admin')->user(),
            (int) $id,
            $request->reason
        );

        return response()->json([
            'success' => true,
            'message' => 'Empresa suspensa com sucesso.',
        ]);
    }

    public function block(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $this->tenantService->blockTenant(
            auth()->guard('admin')->user(),
            (int) $id,
            $request->reason
        );

        return response()->json([
            'success' => true,
            'message' => 'Empresa bloqueada com sucesso.',
        ]);
    }

    public function unblock($id)
    {
        $this->tenantService->unblockTenant(auth()->guard('admin')->user(), (int) $id);

        return response()->json([
            'success' => true,
            'message' => 'Empresa desbloqueada com sucesso.',
        ]);
    }

    public function updatePlan(Request $request, $id)
    {
        $request->validate([
            'plan' => 'required|string',
            'mrr' => 'required|numeric|min:0',
        ]);

        $this->tenantService->updatePlan(
            auth()->guard('admin')->user(),
            (int) $id,
            $request->plan,
            (float) $request->mrr
        );

        return response()->json([
            'success' => true,
            'message' => 'Plano atualizado com sucesso.',
        ]);
    }

    public function pauseAccess(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $this->tenantService->pauseAccess(
            auth()->guard('admin')->user(),
            (int) $id,
            $request->reason
        );

        return response()->json([
            'success' => true,
            'message' => 'Acesso pausado com sucesso. A empresa não poderá fazer login.',
        ]);
    }

    public function restoreAccess($id)
    {
        $this->tenantService->restoreAccess(auth()->guard('admin')->user(), (int) $id);

        return response()->json([
            'success' => true,
            'message' => 'Acesso restaurado com sucesso.',
        ]);
    }

    public function destroy($id)
    {
        $this->tenantService->deleteTenant(auth()->guard('admin')->user(), (int) $id);

        return response()->json([
            'success' => true,
            'message' => 'Empresa excluída com sucesso. Todos os dados foram removidos.',
        ]);
    }

    public function forceDestroy($id)
    {
        $this->tenantService->forceDeleteTenant(auth()->guard('admin')->user(), (int) $id);

        return response()->json([
            'success' => true,
            'message' => 'Empresa excluída PERMANENTEMENTE. Esta ação não pode ser desfeita.',
        ]);
    }
}

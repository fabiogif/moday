<?php

namespace App\Http\Middleware;

use App\Models\AdminActionLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogAdminAction
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Só loga ações de modificação (não GET)
        if ($request->isMethod('get') || $request->isMethod('head')) {
            return $response;
        }

        // Só loga se autenticado como admin
        $admin = auth()->guard('admin')->user();
        if (!$admin) {
            return $response;
        }

        // Só loga se a resposta foi bem-sucedida
        if ($response->getStatusCode() >= 400) {
            return $response;
        }

        // Tenta extrair informações da requisição
        $this->logAction($request, $response, $admin);

        return $response;
    }

    /**
     * Registra a ação administrativa
     */
    protected function logAction(Request $request, Response $response, $admin): void
    {
        try {
            $action = $this->getActionFromRequest($request);
            $description = $this->getDescriptionFromRequest($request);
            $tenantId = $this->getTenantIdFromRequest($request);

            AdminActionLog::create([
                'admin_user_id' => $admin->id,
                'tenant_id' => $tenantId,
                'action' => $action,
                'description' => $description,
                'old_values' => $this->getOldValues($request),
                'new_values' => $this->getNewValues($request),
                'ip_address' => $request->ip(),
            ]);
        } catch (\Exception $e) {
            // Não quebra a requisição se o log falhar
            \Log::error('Erro ao registrar log de ação administrativa: ' . $e->getMessage());
        }
    }

    /**
     * Determina a ação baseada na rota
     */
    protected function getActionFromRequest(Request $request): string
    {
        $method = $request->method();
        $route = $request->route();
        
        if (!$route) {
            return 'unknown';
        }

        $routeName = $route->getName();
        $uri = $request->path();

        // Mapeia ações comuns
        $actionMap = [
            'activate' => 'tenant.activate',
            'suspend' => 'tenant.suspend',
            'block' => 'tenant.block',
            'unblock' => 'tenant.unblock',
            'update-plan' => 'tenant.update_plan',
            'update-limits' => 'tenant.update_limits',
        ];

        foreach ($actionMap as $key => $action) {
            if (str_contains($uri, $key)) {
                return $action;
            }
        }

        // Ações genéricas baseadas no método HTTP
        return match($method) {
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => 'action',
        };
    }

    /**
     * Gera descrição da ação
     */
    protected function getDescriptionFromRequest(Request $request): string
    {
        $method = $request->method();
        $uri = $request->path();
        
        return "{$method} {$uri}";
    }

    /**
     * Tenta extrair tenant_id da requisição
     */
    protected function getTenantIdFromRequest(Request $request): ?int
    {
        // Tenta pegar do corpo da requisição
        if ($request->has('tenant_id')) {
            return $request->input('tenant_id');
        }

        // Tenta pegar dos parâmetros da rota
        $route = $request->route();
        if ($route && $route->hasParameter('id')) {
            $id = $route->parameter('id');
            // Verifica se o ID é de um tenant baseado na URI
            if (str_contains($request->path(), 'tenants')) {
                return $id;
            }
        }

        return null;
    }

    /**
     * Captura valores antigos (se disponível)
     */
    protected function getOldValues(Request $request): ?array
    {
        // Pode ser expandido para capturar estado anterior em updates
        return null;
    }

    /**
     * Captura novos valores
     */
    protected function getNewValues(Request $request): ?array
    {
        // Filtra dados sensíveis
        $data = $request->except(['password', 'password_confirmation', 'token']);
        
        return empty($data) ? null : $data;
    }
}


<?php

namespace App\Services;

use App\Repositories\contracts\ClientRepositoryInterface;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ClientService {

    public function __construct(
        protected ClientRepositoryInterface $clientRepositoryInterface,
        protected CacheService $cacheService
    )
    {
    }

    public function createClient(array $data)
    {
        // Se não foi fornecida uma senha, gerar uma automaticamente
        if (empty($data['password'])) {
            $data['password'] = Str::random(8);
        }
        
        // Gerar UUID se não existir
        if (empty($data['uuid'])) {
            $data['uuid'] = Str::uuid();
        }
        
        $client = $this->clientRepositoryInterface->createClient($data);
        
        // Invalidate cache after creating client
        if ($client && isset($data['tenant_id'])) {
            $this->cacheService->invalidateClientCache($data['tenant_id']);
        }
        
        return $client;
    }

    public function getAllClients()
    {
        return $this->clientRepositoryInterface->getAllClients();
    }

    public function getClientsByTenant($tenantId)
    {
        return $this->cacheService->getClientList($tenantId, function () use ($tenantId) {
            return $this->clientRepositoryInterface->getClientsByTenant($tenantId);
        });
    }

    public function getClientById($id)
    {
        return $this->clientRepositoryInterface->getClientById($id);
    }

    public function updateClient($id, array $data)
    {
        // Se uma nova senha foi fornecida, manter, senão remover do array
        if (empty($data['password'])) {
            unset($data['password']);
        }
        
        $client = $this->clientRepositoryInterface->updateClient($id, $data);
        
        // Invalidate cache after updating client
        if ($client && $client->tenant_id) {
            $this->cacheService->invalidateClientCache($client->tenant_id);
        }
        
        return $client;
    }

    public function deleteClient($id)
    {
        // Get client before deletion to get tenant_id
        $client = $this->clientRepositoryInterface->getClientById($id);
        $tenantId = $client ? $client->tenant_id : null;
        
        $result = $this->clientRepositoryInterface->deleteClient($id);
        
        // Invalidate cache after deleting client
        if ($result && $tenantId) {
            $this->cacheService->invalidateClientCache($tenantId);
        }
        
        return $result;
    }

    /**
     * Get client statistics comparing current month with previous month
     */
    public function getClientStats($tenantId = null)
    {
        if (!$tenantId) {
            return [];
        }

        return $this->cacheService->getClientStats($tenantId, function () use ($tenantId) {
            return $this->calculateClientStats($tenantId);
        });
    }

    /**
     * Calculate client statistics (without cache)
     */
    private function calculateClientStats($tenantId)
    {
        // Data ranges
        $currentMonthStart = Carbon::now()->startOfMonth();
        $currentMonthEnd = Carbon::now()->endOfMonth();
        $previousMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $previousMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        // Base query for clients
        $baseQuery = DB::table('clients');
        if ($tenantId) {
            $baseQuery->where('tenant_id', $tenantId);
        }

        // Total clients
        $currentTotalClients = (clone $baseQuery)->count();
        $previousTotalClients = (clone $baseQuery)
            ->where('created_at', '<=', $previousMonthEnd)
            ->count();

        // Active clients (clients with orders in the last 90 days)
        $activeClientsThreshold = Carbon::now()->subDays(90);
        $currentActiveClients = (clone $baseQuery)
            ->whereExists(function ($query) use ($activeClientsThreshold) {
                $query->select(DB::raw(1))
                    ->from('orders')
                    ->whereColumn('orders.client_id', 'clients.id')
                    ->where('orders.created_at', '>=', $activeClientsThreshold);
            })
            ->count();

        $previousActiveClientsThreshold = Carbon::now()->subMonth()->subDays(90);
        $previousActiveClients = (clone $baseQuery)
            ->where('created_at', '<=', $previousMonthEnd)
            ->whereExists(function ($query) use ($previousActiveClientsThreshold, $previousMonthEnd) {
                $query->select(DB::raw(1))
                    ->from('orders')
                    ->whereColumn('orders.client_id', 'clients.id')
                    ->where('orders.created_at', '>=', $previousActiveClientsThreshold)
                    ->where('orders.created_at', '<=', $previousMonthEnd);
            })
            ->count();

        // Orders per client (average)
        $currentOrdersData = DB::table('clients')
            ->leftJoin('orders', 'clients.id', '=', 'orders.client_id')
            ->when($tenantId, function ($query) use ($tenantId) {
                return $query->where('clients.tenant_id', $tenantId);
            })
            ->selectRaw('
                COUNT(DISTINCT clients.id) as total_clients,
                COUNT(orders.id) as total_orders
            ')
            ->first();

        $previousOrdersData = DB::table('clients')
            ->leftJoin('orders', function ($join) use ($previousMonthEnd) {
                $join->on('clients.id', '=', 'orders.client_id')
                    ->where('orders.created_at', '<=', $previousMonthEnd);
            })
            ->when($tenantId, function ($query) use ($tenantId) {
                return $query->where('clients.tenant_id', $tenantId);
            })
            ->where('clients.created_at', '<=', $previousMonthEnd)
            ->selectRaw('
                COUNT(DISTINCT clients.id) as total_clients,
                COUNT(orders.id) as total_orders
            ')
            ->first();

        $currentOrdersPerClient = $currentOrdersData->total_clients > 0 
            ? round($currentOrdersData->total_orders / $currentOrdersData->total_clients, 1)
            : 0;

        $previousOrdersPerClient = $previousOrdersData->total_clients > 0 
            ? round($previousOrdersData->total_orders / $previousOrdersData->total_clients, 1)
            : 0;

        // New clients (clients created in current month)
        $currentNewClients = (clone $baseQuery)
            ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
            ->count();

        $previousNewClients = (clone $baseQuery)
            ->whereBetween('created_at', [$previousMonthStart, $previousMonthEnd])
            ->count();

        // Calculate growth percentages
        $totalClientsGrowth = $previousTotalClients > 0 
            ? round((($currentTotalClients - $previousTotalClients) / $previousTotalClients) * 100, 1)
            : ($currentTotalClients > 0 ? 100 : 0);

        $activeClientsGrowth = $previousActiveClients > 0 
            ? round((($currentActiveClients - $previousActiveClients) / $previousActiveClients) * 100, 1)
            : ($currentActiveClients > 0 ? 100 : 0);

        $ordersPerClientGrowth = $previousOrdersPerClient > 0 
            ? round((($currentOrdersPerClient - $previousOrdersPerClient) / $previousOrdersPerClient) * 100, 1)
            : ($currentOrdersPerClient > 0 ? 100 : 0);

        $newClientsGrowth = $previousNewClients > 0 
            ? round((($currentNewClients - $previousNewClients) / $previousNewClients) * 100, 1)
            : ($currentNewClients > 0 ? 100 : 0);

        return [
            'total_clients' => [
                'current' => $currentTotalClients,
                'previous' => $previousTotalClients,
                'growth' => $totalClientsGrowth
            ],
            'active_clients' => [
                'current' => $currentActiveClients,
                'previous' => $previousActiveClients,
                'growth' => $activeClientsGrowth
            ],
            'orders_per_client' => [
                'current' => $currentOrdersPerClient,
                'previous' => $previousOrdersPerClient,
                'growth' => $ordersPerClientGrowth
            ],
            'new_clients' => [
                'current' => $currentNewClients,
                'previous' => $previousNewClients,
                'growth' => $newClientsGrowth
            ]
        ];
    }
}

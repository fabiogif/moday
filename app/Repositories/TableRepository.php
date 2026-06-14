<?php

namespace App\Repositories;

use App\Models\Table;
use App\Repositories\Contracts\TableRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TableRepository extends BaseRepository implements TableRepositoryInterface
{

    public function __construct()
    {
        $this->entity = new Table();
    }
    public function getTablesByTenantUuid(string $uuid)
    {
        return $this->entity
            ->join('tenant', 'tenants.id', '=', 'tables.tenant_id')
            ->where('tenant.uuid', $uuid)
            ->select('tables.*')
            ->paginate();
    }

    public function getTablesByTenantId(int $idTenant)
    {
        return $this->entity->where('tenant_id', $idTenant)->paginate();
    }

    public function getTablesByIdentify(string $identify)
    {
        return $this->entity->where('identify', $identify)->get();
    }

    public function getTableByUuid(string $uuid)
    {
        return $this->entity->where('uuid', $uuid)->first();
    }

    public function getStats(int $tenantId): array
    {
        $totalTables = $this->entity->where('tenant_id', $tenantId)->count();
        
        // Para simular mesas ocupadas, vou usar uma lógica baseada em pedidos ativos
        // Assumindo que temos uma tabela de orders com status ativo
        $occupiedTables = DB::table('orders')
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['Em Preparo', 'Pronto'])
            ->whereNotNull('table_id')
            ->distinct('table_id')
            ->count();
        
        $availableTables = $totalTables - $occupiedTables;
        $occupancyRate = $totalTables > 0 ? round(($occupiedTables / $totalTables) * 100, 1) : 0;
        
        return [
            'total_tables' => $totalTables,
            'occupied_tables' => $occupiedTables,
            'available_tables' => $availableTables,
            'occupancy_rate' => $occupancyRate
        ];
    }
    public function delete(string $identify)
    {
        return $this->entity->where('uuid',  $identify)->update(['is_active'=> '0']);
    }
}

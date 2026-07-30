<?php


namespace App\Repositories;

use App\Models\Client;
use App\Repositories\Contracts\ClientRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ClientRepository extends BaseRepository implements ClientRepositoryInterface
{
    public function __construct(protected Model $entity =  new Client())
    {
    }

    public function createClient(array $data)
    {
        $data['password'] = bcrypt($data['password']);
        return $this->entity->create($data);
    }

    public function getAllClients()
    {
        return $this->entity
            ->withCount(['orders', 'saleOrders'])
            ->withMax('orders', 'created_at')
            ->withMax('saleOrders', 'ordered_at')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getClientsByTenant($tenantId)
    {
        return $this->entity->where('tenant_id', $tenantId)
            ->withCount(['orders', 'saleOrders'])
            ->withMax('orders', 'created_at')
            ->withMax('saleOrders', 'ordered_at')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function paginateForTenant(int $tenantId, int $page, int $perPage, ?string $search = null)
    {
        $query = $this->entity->where('tenant_id', $tenantId)
            ->withCount(['orders', 'saleOrders'])
            ->withMax('orders', 'created_at')
            ->withMax('saleOrders', 'ordered_at')
            ->orderBy('created_at', 'desc');

        if ($search !== null && $search !== '') {
            $term = '%' . $search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('company_name', 'like', $term)
                    ->orWhere('trade_name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term);
            });
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    public function getClientById($id)
    {
        return $this->entity
            ->withCount(['orders', 'saleOrders'])
            ->withMax('orders', 'created_at')
            ->withMax('saleOrders', 'ordered_at')
            ->find($id);
    }

    public function getClientByUuid($uuid)
    {
        return $this->entity->where('uuid', $uuid)->first();
    }

    public function updateClient($id, array $data)
    {
        if (isset($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        }
        
        $client = $this->entity->find($id);
        if (!$client) {
            return null;
        }
        
        $client->update($data);
        return $client->fresh();
    }

    public function deleteClient($id)
    {
        $client = $this->entity->find($id);
        if (!$client) {
            return false;
        }
        
        return $client->delete();
    }

    public function getTableByIdentify(string $identify)
    {
        // TODO: Implement getTableByIdentify() method.
    }

    public function findByCpfAndTenant(string $cpf, int $tenantId)
    {
        return $this->entity
            ->where('cpf', $cpf)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    public function findByRequestIdAndTenant(string $requestId, int $tenantId)
    {
        return $this->entity
            ->where('client_request_id', $requestId)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    public function findByEmailAndTenant(string $email, int $tenantId)
    {
        return $this->entity
            ->where('email', $email)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    public function findByPhoneAndTenant(string $phone, int $tenantId)
    {
        $clean = preg_replace('/\D/', '', $phone);
        if (strlen($clean) < 10) {
            return null;
        }

        $suffix = strlen($clean) > 11 ? substr($clean, -11) : $clean;

        return $this->entity
            ->where('tenant_id', $tenantId)
            ->where(function ($q) use ($clean, $suffix) {
                $q->where('phone', $clean)
                    ->orWhere('phone', $suffix)
                    ->orWhere('phone', 'like', '%' . $suffix);
            })
            ->first();
    }

    public function emailExistsForTenant(string $email, int $tenantId): bool
    {
        return $this->entity
            ->where('email', $email)
            ->where('tenant_id', $tenantId)
            ->exists();
    }

    public function cpfExistsForOtherClient(string $cpf, int $tenantId, int $excludeId): bool
    {
        return $this->entity
            ->where('cpf', $cpf)
            ->where('tenant_id', $tenantId)
            ->where('id', '!=', $excludeId)
            ->exists();
    }

    public function emailExistsForOtherClient(string $email, int $tenantId, int $excludeId): bool
    {
        return $this->entity
            ->where('email', $email)
            ->where('tenant_id', $tenantId)
            ->where('id', '!=', $excludeId)
            ->exists();
    }

    public function registerAuthClient(array $data, int $tenantId)
    {
        return $this->entity->create([
            'uuid' => Str::uuid(),
            'tenant_id' => $tenantId,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'],
            'cpf' => $data['cpf'] ?? null,
            'is_active' => true,
        ]);
    }

    public function createPublicClient(array $data)
    {
        return $this->entity->create($data);
    }
}

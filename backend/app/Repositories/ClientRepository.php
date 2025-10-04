<?php


namespace App\Repositories;

use App\Models\Client;
use App\Repositories\contracts\ClientRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

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
        return $this->entity->with(['orders' => function($query) {
            $query->orderBy('created_at', 'desc');
        }])->orderBy('created_at', 'desc')->get();
    }

    public function getClientsByTenant($tenantId)
    {
        return $this->entity->where('tenant_id', $tenantId)
            ->with(['orders' => function($query) {
                $query->orderBy('created_at', 'desc');
            }])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getClientById($id)
    {
        return $this->entity->with(['orders' => function($query) {
            $query->orderBy('created_at', 'desc');
        }])->find($id);
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
}

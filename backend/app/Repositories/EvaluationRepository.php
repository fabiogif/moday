<?php

namespace App\Repositories;

use App\Models\OrderEvaluation;
use App\Repositories\contracts\EvaluationRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class EvaluationRepository extends BaseRepository implements  EvaluationRepositoryInterface
{
    public function __construct(protected Model $entity =  new OrderEvaluation())
    {
    }

    public function newEvaluationOrder(int $orderId, int $clientId, int $tenantId, array $evaluation)
    {
        $data = [
            'order_id' => $orderId,
            'client_id' => $clientId,
            'tenant_id' => $tenantId,
            'stars' => $evaluation['stars'],
            'comment' => $evaluation['comment'] ?? null,
        ];
        return $this->entity->create($data);
    }

    public function getEvaluationsByOrder(int $orderId)
    {
        return $this->entity->order()->where('order_id', $orderId)->get();
    }

    public function getEvaluationsByClient(int $clientId)
    {
        return $this->entity->client()->where('client_id', $clientId)->get();
    }

    public function getEvaluationsByClientIdByOrderId(int $clientId, int $orderId)
    {
        return $this->entity->order()->where('client_id', $clientId)->where('order_id', $orderId)->get();
    }
}

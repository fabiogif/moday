<?php

namespace App\Repositories\Contracts;

interface EvaluationRepositoryInterface extends BaseRepositoryInterface
{
    public function newEvaluationOrder(int $orderId, int $clientId, int $tenantId, array $evaluation);
    public function getEvaluationsByOrder(int $orderId);
    public function getEvaluationsByClient(int $clientId);
    public function getEvaluationsByClientIdByOrderId(int $clientId, int $orderId);


}

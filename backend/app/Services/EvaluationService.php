<?php

namespace App\Services;

use App\Repositories\contracts\{EvaluationRepositoryInterface, OrderRepositoryInterface};

readonly class EvaluationService
{
    public function __construct(
        protected EvaluationRepositoryInterface $evaluationRepositoryInterface,
        protected OrderRepositoryInterface $orderRepositoryInterface)
    {}

    public function newCeateEvaluation(string $identifyOrder, array $evaluation)
    {
        $clientId = $this->getIdClient();

        $order = $this->orderRepositoryInterface->getOrderByIdentify($identifyOrder);

        return $this->evaluationRepositoryInterface->newEvaluationOrder(orderId: $order->id,
                                                                        clientId: $clientId,
                                                                        tenantId: $order->tenant_id,
                                                                        evaluation: $evaluation);
    }

    private function getIdClient()
    {
        return auth()->check() ? auth()->user()->id : '';
    }

    public function getEvaluationsByOrder($orderId)
    {
        return $this->evaluationRepositoryInterface->getEvaluationsByOrder($orderId);
    }
    public function getEvaluationsByClientIdByOrderId(int $clientId, int $orderId)
    {
        return $this->evaluationRepositoryInterface->getEvaluationsByClientIdByOrderId($clientId, $orderId);
    }

    public function getEvaluationsByClient(int $clientId)
    {
        return $this->evaluationRepositoryInterface->getEvaluationsByClient($clientId);
    }
}

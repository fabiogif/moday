<?php

namespace App\Services;

use App\Repositories\Contracts\LoyaltyRewardRepositoryInterface;

readonly class LoyaltyRewardService
{
    public function __construct(
        private LoyaltyRewardRepositoryInterface $rewardRepository,
        private LoyaltyProgramService $programService,
        private AuthTenantService $authTenantService
    ) {}

    public function listForAuthenticatedTenant(bool $availableOnly = false): array
    {
        [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
        $program = $this->programService->getProgramByTenant($tenantId);

        if (!$program) {
            return ['program' => null, 'rewards' => collect()];
        }

        $rewards = $availableOnly
            ? $this->rewardRepository->getAvailableByProgram($program->id)
            : $this->rewardRepository->getAllByProgram($program->id);

        return ['program' => $program, 'rewards' => $rewards];
    }

    public function createForAuthenticatedTenant(array $data): array
    {
        [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
        $program = $this->programService->getProgramByTenant($tenantId);

        if (!$program) {
            return ['success' => false, 'reason' => 'no_program'];
        }

        $data['loyalty_program_id'] = $program->id;

        return [
            'success' => true,
            'reward' => $this->rewardRepository->create($data),
        ];
    }

    public function findForAuthenticatedTenant(string $uuid): array
    {
        [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
        $reward = $this->rewardRepository->getByUuid($uuid);

        if (!$reward || $reward->loyaltyProgram->tenant_id !== $tenantId) {
            return ['success' => false];
        }

        return ['success' => true, 'reward' => $reward];
    }

    public function updateForAuthenticatedTenant(string $uuid, array $data): array
    {
        $found = $this->findForAuthenticatedTenant($uuid);
        if (!$found['success']) {
            return $found;
        }

        return [
            'success' => true,
            'reward' => $this->rewardRepository->update($found['reward']->id, $data),
        ];
    }

    public function deleteForAuthenticatedTenant(string $uuid): array
    {
        $found = $this->findForAuthenticatedTenant($uuid);
        if (!$found['success']) {
            return $found;
        }

        $this->rewardRepository->delete($found['reward']->id);

        return ['success' => true];
    }
}

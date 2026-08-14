<?php

namespace App\Services;

use App\Events\CompanyRegistered;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class TenantRegistrationService
{
    public function __construct(
        private readonly PlanRepositoryInterface $planRepository,
        private readonly TenantRepositoryInterface $tenantRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly TenantAclProvisioner $tenantAclProvisioner,
        private readonly TenantFinancialCategoryProvisioner $financialCategoryProvisioner,
        private readonly EmailVerificationService $emailVerification,
    ) {}

    /**
     * @return array{tenant: Tenant, user: User, plan: Plan, token: string, trial_status: array, email_verified: bool}
     * @throws \Throwable
     */
    public function register(array $data): array
    {
        DB::beginTransaction();

        try {
            /** @var Plan|null $plan */
            $plan = $this->planRepository->getById((int) $data['plan_id']);
            if (!$plan) {
                throw new \RuntimeException("Plano {$data['plan_id']} não encontrado");
            }

            $tenant = $this->tenantRepository->createRegisteredTenant($data, $plan);

            $user = $this->userRepository->createUser([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'phone' => $data['phone'] ?? null,
                'tenant_id' => $tenant->id,
                'is_active' => true,
                'email_verified_at' => null,
            ]);

            $this->tenantAclProvisioner->provisionAndAssignOwner($tenant, $user);
            $this->financialCategoryProvisioner->provision($tenant);

            $token = JWTAuth::fromUser($user);
            $user = $this->userRepository->loadRelations($user, ['tenant', 'profiles.permissions']);

            DB::commit();

            Log::info('Novo tenant registrado com sucesso', [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'plan_id' => $plan->id,
            ]);

            event(new CompanyRegistered($tenant, $user, $plan));

            try {
                $this->emailVerification->send($user);
            } catch (\Throwable $e) {
                Log::error('Falha ao enviar verificação de e-mail no registro', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return [
                'tenant' => $tenant,
                'user' => $user,
                'plan' => $plan,
                'token' => $token,
                'trial_status' => $tenant->toTrialStatusArray(),
                'email_verified' => false,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Erro no registro de tenant', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}

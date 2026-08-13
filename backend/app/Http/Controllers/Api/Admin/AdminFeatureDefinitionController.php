<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Models\Plan;
use App\Repositories\Contracts\FeatureDefinitionRepositoryInterface;
use App\Services\PlanFeatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminFeatureDefinitionController extends Controller
{
    public function __construct(
        private readonly FeatureDefinitionRepositoryInterface $featureDefinitionRepository,
        private readonly PlanFeatureService $featureService,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json(['data' => $this->featureDefinitionRepository->allOrdered()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'key'           => 'required|string|unique:feature_definitions,key',
            'name'          => 'required|string|max:255',
            'description'   => 'nullable|string',
            'category'      => 'required|string|max:100',
            'icon'          => 'nullable|string|max:100',
            'plan_tier'     => 'required|in:basic,professional,enterprise',
            'display_order' => 'integer|min:0',
            'is_active'     => 'boolean',
        ]);

        $feature = $this->featureDefinitionRepository->create($data);

        return response()->json(['data' => $feature], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'name'          => 'sometimes|string|max:255',
            'description'   => 'nullable|string',
            'category'      => 'sometimes|string|max:100',
            'icon'          => 'nullable|string|max:100',
            'plan_tier'     => 'sometimes|in:basic,professional,enterprise',
            'display_order' => 'integer|min:0',
            'is_active'     => 'boolean',
        ]);

        $feature = $this->featureDefinitionRepository->update($id, $data);

        return response()->json(['data' => $feature]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->featureDefinitionRepository->delete($id);

        return response()->json(['message' => 'Feature removida com sucesso']);
    }

    /** GET /admin/plans/{planId}/features */
    public function planFeatures(int $planId): JsonResponse
    {
        Plan::findOrFail($planId);
        $features = $this->featureService->getFeaturesForPlan($planId);

        return response()->json(['data' => $features]);
    }

    /** POST /admin/plans/{planId}/features/sync */
    public function syncPlanFeatures(Request $request, int $planId): JsonResponse
    {
        Plan::findOrFail($planId);

        $data = $request->validate([
            'features'   => 'required|array',
            'features.*' => 'boolean',
        ]);

        $this->featureService->syncFeatures($planId, $data['features']);

        return response()->json([
            'message' => 'Features do plano atualizadas com sucesso',
            'data'    => $this->featureService->getFeaturesForPlan($planId),
        ]);
    }
}

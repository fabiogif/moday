<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminMetricsService;
use Illuminate\Http\Request;

class AdminMetricsController extends Controller
{
    public function __construct(
        private readonly AdminMetricsService $metricsService
    ) {}

    public function revenue(Request $request)
    {
        $startDate = $request->input('start_date', now()->subMonths(6)->startOfMonth());
        $endDate = $request->input('end_date', now());

        return response()->json([
            'success' => true,
            'data' => $this->metricsService->getRevenueMetrics($startDate, $endDate),
        ]);
    }

    public function usage(Request $request)
    {
        $startDate = $request->input('start_date', now()->subDays(30));
        $endDate = $request->input('end_date', now());

        return response()->json([
            'success' => true,
            'data' => $this->metricsService->getUsageMetrics($startDate, $endDate),
        ]);
    }

    public function messages(Request $request)
    {
        $startDate = $request->input('start_date', now()->subDays(30));
        $endDate = $request->input('end_date', now());

        return response()->json([
            'success' => true,
            'data' => $this->metricsService->getMessageMetrics($startDate, $endDate),
        ]);
    }

    public function growth(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $this->metricsService->getGrowthMetrics(),
        ]);
    }

    public function tenantMetrics(Request $request, $tenantId)
    {
        $startDate = $request->input('start_date', now()->subDays(30));
        $endDate = $request->input('end_date', now());

        return response()->json([
            'success' => true,
            'data' => $this->metricsService->getTenantMetrics((int) $tenantId, $startDate, $endDate),
        ]);
    }
}

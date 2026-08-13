<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminDashboardService;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function __construct(
        private readonly AdminDashboardService $dashboardService
    ) {}

    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getDashboardStats(),
        ]);
    }

    public function recentActivity(Request $request)
    {
        $limit = (int) $request->input('limit', 20);

        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getRecentActivity($limit),
        ]);
    }

    public function alerts()
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardService->getAlerts(),
        ]);
    }
}

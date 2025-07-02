<?php

namespace App\Modules\Dashboard\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Dashboard\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index(): JsonResponse
    {
        try {
            $data = $this->dashboardService->getDashboardData();
            return $this->sendResponse($data, 'Dashboard data retrieved successfully!');
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve dashboard data.', ['error' => $e->getMessage()], 500);
        }
    }

    public function officeAssistant(Request $request): JsonResponse
    {
        try {
            $data = $this->dashboardService->getOfficeAssistantData();
            return $this->sendResponse($data, 'Office Assistant dashboard data retrieved successfully!');
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve Office Assistant dashboard data.', ['error' => $e->getMessage()], 500);
        }
    }

    public function researchSupervisor(Request $request): JsonResponse
    {
        try {
            $data = $this->dashboardService->getResearchSupervisorData();
            return $this->sendResponse($data, 'Research Supervisor dashboard data retrieved successfully!');
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve Research Supervisor dashboard data.', ['error' => $e->getMessage()], 500);
        }
    }

    public function programCoordinator(Request $request): JsonResponse
    {
        try {
            $data = $this->dashboardService->getProgramCoordinatorData();
            return $this->sendResponse($data, 'Program Coordinator dashboard data retrieved successfully!');
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve Program Coordinator dashboard data.', ['error' => $e->getMessage()], 500);
        }
    }

    public function pgam(Request $request): JsonResponse
    {
        try {
            $data = $this->dashboardService->getPGAMData();
            return $this->sendResponse($data, 'PGAM dashboard data retrieved successfully!');
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve PGAM dashboard data.', ['error' => $e->getMessage()], 500);
        }
    }

    public function systemOverview(Request $request): JsonResponse
    {
        try {
            $data = $this->dashboardService->getSystemOverview();
            return $this->sendResponse($data, 'System overview retrieved successfully!');
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve system overview.', ['error' => $e->getMessage()], 500);
        }
    }

    public function recentActivity(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 10);
            $data = $this->dashboardService->getRecentActivity($limit);
            return $this->sendResponse($data, 'Recent activity retrieved successfully!');
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve recent activity.', ['error' => $e->getMessage()], 500);
        }
    }
}

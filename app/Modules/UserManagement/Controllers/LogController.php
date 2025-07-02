<?php

namespace App\Modules\UserManagement\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Log;
use App\Enums\ActionType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LogController extends Controller
{
    /**
     * Get paginated logs with optional filters
     */
    public function index(Request $request): JsonResponse
    {
        $query = Log::with('user')->orderBy('performed_at', 'desc');

        // Apply filters
        if ($request->filled('action_type')) {
            $query->where('action_type', $request->action_type);
        }

        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('performed_at', [$request->start_date, $request->end_date]);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('entity_type', 'like', "%{$search}%")
                  ->orWhere('entity_id', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        // Handle pagination
        $perPage = $request->get('per_page', 15);
        if ($perPage <= 0) {
            $logs = $query->get();
            return response()->json([
                'success' => true,
                'data' => $logs,
                'meta' => [
                    'total' => $logs->count(),
                    'per_page' => -1,
                    'current_page' => 1,
                    'last_page' => 1,
                    'from' => 1,
                    'to' => $logs->count(),
                ]
            ]);
        }

        $logs = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'meta' => [
                'total' => $logs->total(),
                'per_page' => $logs->perPage(),
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'from' => $logs->firstItem(),
                'to' => $logs->lastItem(),
            ]
        ]);
    }

    /**
     * Get a specific log entry
     */
    public function show($id): JsonResponse
    {
        $log = Log::with('user')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $log
        ]);
    }

    /**
     * Get log statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $query = Log::query();

        // Apply date filter if provided
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('performed_at', [$request->start_date, $request->end_date]);
        }

        $stats = [
            'total_actions' => $query->count(),
            'successful_actions' => $query->clone()->where('status', 'SUCCESS')->count(),
            'failed_actions' => $query->clone()->where('status', 'FAILURE')->count(),
            'actions_by_type' => $query->clone()
                ->selectRaw('action_type, COUNT(*) as count')
                ->groupBy('action_type')
                ->pluck('count', 'action_type')
                ->toArray(),
            'actions_by_entity' => $query->clone()
                ->selectRaw('entity_type, COUNT(*) as count')
                ->groupBy('entity_type')
                ->pluck('count', 'entity_type')
                ->toArray(),
            'top_users' => $query->clone()
                ->selectRaw('user_id, username, COUNT(*) as count')
                ->whereNotNull('user_id')
                ->groupBy('user_id', 'username')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get()
                ->toArray(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get available action types
     */
    public function actionTypes(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => ActionType::forSelect()
        ]);
    }

    /**
     * Get unique entity types from logs
     */
    public function entityTypes(): JsonResponse
    {
        $entityTypes = Log::distinct()
            ->whereNotNull('entity_type')
            ->where('entity_type', '!=', 'Unknown')
            ->pluck('entity_type')
            ->sort()
            ->values();

        return response()->json([
            'success' => true,
            'data' => $entityTypes
        ]);
    }

    /**
     * Clear old logs (keep last 30 days by default)
     */
    public function clearOldLogs(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        $cutoffDate = now()->subDays($days);

        $deletedCount = Log::where('performed_at', '<', $cutoffDate)->delete();

        return response()->json([
            'success' => true,
            'message' => "Deleted {$deletedCount} log entries older than {$days} days",
            'deleted_count' => $deletedCount
        ]);
    }
} 
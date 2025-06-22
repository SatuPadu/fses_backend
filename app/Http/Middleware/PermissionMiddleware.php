<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Modules\UserManagement\Services\PermissionService;

class PermissionMiddleware
{
    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string $module
     * @param string $action
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $module, string $action = null)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access'
            ], 401);
        }

        // If no specific action is provided, check if user has any permission for the module
        if (!$action) {
            if (!$this->permissionService->userCan($user, $module, 'view')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Access denied. Insufficient permissions.',
                ], 403);
            }
        } else {
            // Check specific permission
            if (!$this->permissionService->userCan($user, $module, $action)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Access denied. Insufficient permissions for this action.',
                ], 403);
            }
        }

        return $next($request);
    }
} 
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Modules\UserManagement\Services\PermissionService;
use App\Enums\UserRole;

class RoleMiddleware
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
     * @param string $role
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $role)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access'
            ], 401);
        }

        // Check if user has the required role
        if (!$this->permissionService->userHasSpecificRole($user, $role)) {
            return response()->json([
                'status' => 'error',
                'message' => "Access denied. {$role} role required."
            ], 403);
        }

        return $next($request);
    }
} 
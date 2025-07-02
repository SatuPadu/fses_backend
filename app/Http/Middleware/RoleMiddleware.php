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
     * @param string $roles
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $roles)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access'
            ], 401);
        }

        // Always allow PGAM
        if ($this->permissionService->userHasSpecificRole($user, UserRole::PGAM)) {
            return $next($request);
        }

        // Accept comma-separated roles, check if user has any
        $roleList = array_map('trim', explode(',', $roles));
        if ($this->permissionService->userHasRole($user, $roleList)) {
            return $next($request);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Access denied. ' . implode(' or ', $roleList) . ' role required.'
        ], 403);
    }
} 
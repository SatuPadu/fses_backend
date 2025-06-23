<?php

namespace App\Modules\UserManagement\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\UserManagement\Services\RoleService;
use App\Modules\UserManagement\Requests\AssignPGAMRoleRequest;
use App\Modules\UserManagement\Requests\CheckPermissionRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * @OA\Tag(
 *     name="Role Management",
 *     description="API Endpoints related to Role Management"
 * )
 */
class RoleController extends Controller
{
    protected $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    /**
     * Get all roles
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $roles = $this->roleService->getAllRoles();
            return $this->sendResponse($roles, 'Roles retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError(
                'An unexpected error occurred. Please try again later.',
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get a specific role
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $role = $this->roleService->getRoleById($id);
            
            if (!$role) {
                return $this->sendError(
                    'Role not found',
                    ['error' => 'Role does not exist'],
                    Response::HTTP_NOT_FOUND
                );
            }

            return $this->sendResponse($role, 'Role retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError(
                'An unexpected error occurred. Please try again later.',
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get current user's permissions and capabilities
     *
     * @return JsonResponse
     */
    public function getUserPermissions(): JsonResponse
    {
        try {
            $permissions = $this->roleService->getUserPermissions();
            return $this->sendResponse($permissions, 'User permissions retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError(
                'An unexpected error occurred. Please try again later.',
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Check if user has specific permission
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkPermission(Request $request): JsonResponse
    {
        try {
            $validated = CheckPermissionRequest::validate($request);
            $result = $this->roleService->checkUserPermission(
                $validated['module'],
                $validated['action']
            );
            return $this->sendResponse($result, 'Permission check completed');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendValidationError($e->errors());
        } catch (\Exception $e) {
            return $this->sendError(
                'An unexpected error occurred. Please try again later.',
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Assign PGAM role to a user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function assignPGAMRole(Request $request): JsonResponse
    {
        try {
            $validated = AssignPGAMRoleRequest::validate($request);
            $result = $this->roleService->assignPGAMRole($validated['user_id']);
            return $this->sendResponse($result, 'PGAM role assigned successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendValidationError($e->errors());
        } catch (\Exception $e) {
            return $this->sendError(
                'An unexpected error occurred. Please try again later.',
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get PGAM users
     *
     * @return JsonResponse
     */
    public function getPGAMUsers(): JsonResponse
    {
        try {
            $result = $this->roleService->getPGAMUsers();
            return $this->sendResponse($result, 'PGAM users retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError(
                'An unexpected error occurred. Please try again later.',
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
} 
<?php

namespace App\Modules\UserManagement\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Modules\UserManagement\Models\Role;
use App\Modules\UserManagement\Services\PermissionService;
use App\Helpers\PermissionHelper;

/**
 * @OA\Tag(
 *     name="Role Management",
 *     description="API Endpoints related to Role Management"
 * )
 */
class RoleController extends Controller
{
    use PermissionHelper;

    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Get all roles
     */
    public function index(): JsonResponse
    {
        try {
            // Check if user has permission to view roles
            if (!$this->userCan('users', 'view')) {
                return $this->sendError('Access denied. Insufficient permissions.', [], 403);
            }

            $roles = Role::with('users')->get();

            return $this->sendResponse($roles, 'Roles retrieved successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve roles.', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get a specific role
     */
    public function show(int $id): JsonResponse
    {
        try {
            // Check if user has permission to view roles
            if (!$this->userCan('users', 'view')) {
                return $this->sendError('Access denied. Insufficient permissions.', [], 403);
            }

            $role = Role::with('users')->find($id);

            if (!$role) {
                return $this->sendError('Role not found.', [], 404);
            }

            return $this->sendResponse($role, 'Role retrieved successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve role.', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get current user's permissions
     */
    public function myPermissions(): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return $this->sendError('User not authenticated.', [], 401);
            }

            $permissions = $user->permissions;
            $roles = $user->roles;
            $accessibleModules = $this->permissionService->getUserAccessibleModules($user);

            return $this->sendResponse([
                'permissions' => $permissions,
                'roles' => $roles,
                'accessible_modules' => $accessibleModules,
                'capabilities' => [
                    'can_manage_students' => $this->canManageStudents(),
                    'can_manage_users' => $this->canManageUsers(),
                    'can_nominate_examiners' => $this->canNominateExaminers(),
                    'can_assign_chairpersons' => $this->canAssignChairpersons(),
                    'can_lock_nominations' => $this->canLockNominations(),
                    'can_view_reports' => $this->canViewReports(),
                    'can_manage_programs' => $this->canManagePrograms(),
                    'can_manage_lecturers' => $this->canManageLecturers(),
                ]
            ], 'User permissions retrieved successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve user permissions.', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Check if user has specific permission
     */
    public function checkPermission(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'module' => 'required|string',
                'action' => 'required|string'
            ]);

            $module = $request->input('module');
            $action = $request->input('action');

            $hasPermission = $this->userCan($module, $action);

            return $this->sendResponse([
                'has_permission' => $hasPermission,
                'module' => $module,
                'action' => $action
            ], 'Permission check completed.');
        } catch (\Exception $e) {
            return $this->sendError('Failed to check permission.', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Assign PGAM role to a user
     */
    public function assignPGAMRole(Request $request): JsonResponse
    {
        try {
            // Check if user has permission to manage users
            if (!$this->userCan('users', 'edit')) {
                return $this->sendError('Access denied. Insufficient permissions.', [], 403);
            }

            $request->validate([
                'user_id' => 'required|integer|exists:users,id'
            ]);

            $userId = $request->input('user_id');
            $user = \App\Modules\Auth\Models\User::findOrFail($userId);
            
            // Get PGAM role
            $pgamRole = Role::findByName('PGAM');
            
            if (!$pgamRole) {
                return $this->sendError('PGAM role not found.', [], 404);
            }

            // Check if user already has PGAM role
            if ($user->hasRole('PGAM')) {
                return $this->sendError('User already has PGAM role.', [], 400);
            }

            // Assign PGAM role to user
            $user->roles()->attach($pgamRole->id);

            return $this->sendResponse([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'role_assigned' => 'PGAM'
            ], 'PGAM role assigned successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendValidationError($e->errors());
        } catch (\Exception $e) {
            return $this->sendError('Failed to assign PGAM role.', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get PGAM user details with lecturer data
     */
    public function getPGAMUser(): JsonResponse
    {
        try {
            // Check if user has permission to view users
            if (!$this->userCan('users', 'view')) {
                return $this->sendError('Access denied. Insufficient permissions.', [], 403);
            }

            // Get PGAM role
            $pgamRole = Role::findByName('PGAM');
            
            if (!$pgamRole) {
                return $this->sendError('PGAM role not found.', [], 404);
            }

            // Get users with PGAM role and their lecturer data
            $pgamUsers = $pgamRole->users()
                ->with(['lecturer', 'roles'])
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'staff_number' => $user->staff_number,
                        'name' => $user->name,
                        'email' => $user->email,
                        'department' => $user->department,
                        'is_active' => $user->is_active,
                        'is_password_updated' => $user->is_password_updated,
                        'last_login' => $user->last_login,
                        'created_at' => $user->created_at,
                        'updated_at' => $user->updated_at,
                        'lecturer' => $user->lecturer ? [
                            'id' => $user->lecturer->id,
                            'name' => $user->lecturer->name,
                            'email' => $user->lecturer->email,
                            'staff_number' => $user->lecturer->staff_number,
                            'phone' => $user->lecturer->phone,
                            'department' => $user->lecturer->department,
                            'title' => $user->lecturer->title,
                            'is_from_fai' => $user->lecturer->is_from_fai,
                            'external_institution' => $user->lecturer->external_institution,
                            'specialization' => $user->lecturer->specialization,
                            'created_at' => $user->lecturer->created_at,
                            'updated_at' => $user->lecturer->updated_at,
                        ] : null,
                        'roles' => $user->roles->map(function ($role) {
                            return [
                                'id' => $role->id,
                                'role_name' => $role->role_name,
                                'description' => $role->description,
                                'permissions' => $role->permissions,
                            ];
                        })
                    ];
                });

            return $this->sendResponse([
                'pgam_users' => $pgamUsers,
                'total_count' => $pgamUsers->count()
            ], 'PGAM users retrieved successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve PGAM users.', ['error' => $e->getMessage()], 500);
        }
    }
} 
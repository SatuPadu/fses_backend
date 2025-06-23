<?php

namespace App\Modules\UserManagement\Services;

use App\Modules\UserManagement\Models\Role;
use App\Modules\Auth\Models\User;
use App\Modules\UserManagement\Services\PermissionService;
use App\Helpers\PermissionHelper;
use Exception;

class RoleService
{
    use PermissionHelper;

    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Get all roles with users
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllRoles()
    {
        return Role::with('users')->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get a specific role by ID
     *
     * @param int $id
     * @return Role|null
     */
    public function getRoleById(int $id)
    {
        return Role::with('users')->find($id);
    }

    /**
     * Get current user's permissions and capabilities
     *
     * @return array
     */
    public function getUserPermissions(): array
    {
        $user = auth()->user();
        
        if (!$user) {
            throw new Exception('User not authenticated.');
        }

        $permissions = $user->permissions;
        $roles = $user->roles;
        $accessibleModules = $this->permissionService->getUserAccessibleModules($user);

        return [
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
        ];
    }

    /**
     * Check if user has specific permission
     *
     * @param string $module
     * @param string $action
     * @return array
     */
    public function checkUserPermission(string $module, string $action): array
    {
        $hasPermission = $this->userCan($module, $action);

        return [
            'has_permission' => $hasPermission,
            'module' => $module,
            'action' => $action
        ];
    }

    /**
     * Assign PGAM role to a user
     *
     * @param int $userId
     * @return array
     * @throws Exception
     */
    public function assignPGAMRole(int $userId): array
    {
        $user = User::findOrFail($userId);
        
        // Get PGAM role
        $pgamRole = Role::findByName('PGAM');
        
        if (!$pgamRole) {
            throw new Exception('PGAM role not found.');
        }

        // Check if user already has PGAM role
        if ($user->hasRole('PGAM')) {
            throw new Exception('User already has PGAM role.');
        }

        // Assign PGAM role to user
        $user->roles()->attach($pgamRole->id);

        return [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'role_assigned' => 'PGAM'
        ];
    }

    /**
     * Get PGAM users with lecturer data
     *
     * @return array
     * @throws Exception
     */
    public function getPGAMUsers(): array
    {
        // Get PGAM role
        $pgamRole = Role::findByName('PGAM');
        
        if (!$pgamRole) {
            throw new Exception('PGAM role not found.');
        }

        // Get users with PGAM role and their lecturer data
        $pgamUsers = $pgamRole->users()
            ->with(['lecturer', 'roles'])
            ->orderBy('created_at', 'desc')
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

        return [
            'pgam_users' => $pgamUsers,
            'total_count' => $pgamUsers->count()
        ];
    }
} 
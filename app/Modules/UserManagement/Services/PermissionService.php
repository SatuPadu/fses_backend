<?php

namespace App\Modules\UserManagement\Services;

use App\Modules\Auth\Models\User;
use App\Enums\UserRole;

class PermissionService
{
    /**
     * Check if user has permission for a specific module and action
     */
    public function userCan(User $user, string $module, string $action): bool
    {
        return $user->hasPermissionFor($module, $action);
    }

    /**
     * Check if user has any of the specified roles
     */
    public function userHasRole(User $user, array $roles): bool
    {
        return $user->hasAnyRole($roles);
    }

    /**
     * Check if user has a specific role
     */
    public function userHasSpecificRole(User $user, string $role): bool
    {
        return $user->hasRole($role);
    }

    /**
     * Get user's accessible modules based on their roles
     */
    public function getUserAccessibleModules(User $user): array
    {
        $permissions = $user->getAllPermissions();
        return array_keys($permissions);
    }

    /**
     * Check if user can access student management features
     */
    public function canManageStudents(User $user): bool
    {
        return $user->hasPermissionFor('students', 'view') || 
               $user->hasPermissionFor('students', 'create') || 
               $user->hasPermissionFor('students', 'edit');
    }

    /**
     * Check if user can manage users (Office Assistant, Program Coordinator, PGAM)
     */
    public function canManageUsers(User $user): bool
    {
        return $user->hasAnyRole([
            UserRole::OFFICE_ASSISTANT,
            UserRole::PROGRAM_COORDINATOR,
            UserRole::PGAM
        ]);
    }

    /**
     * Check if user can nominate examiners (Research Supervisor)
     */
    public function canNominateExaminers(User $user): bool
    {
        return $user->hasRole(UserRole::SUPERVISOR);
    }

    /**
     * Check if user can assign chairpersons (Program Coordinator)
     */
    public function canAssignChairpersons(User $user): bool
    {
        return $user->hasRole(UserRole::PROGRAM_COORDINATOR);
    }

    /**
     * Check if user can lock nominations (Program Coordinator)
     */
    public function canLockNominations(User $user): bool
    {
        return $user->hasRole(UserRole::PROGRAM_COORDINATOR);
    }

    /**
     * Check if user can view reports (Program Coordinator, PGAM)
     */
    public function canViewReports(User $user): bool
    {
        return $user->hasAnyRole([
            UserRole::PROGRAM_COORDINATOR,
            UserRole::PGAM
        ]);
    }

    /**
     * Check if user can manage programs (Program Coordinator, PGAM)
     */
    public function canManagePrograms(User $user): bool
    {
        return $user->hasAnyRole([
            UserRole::PROGRAM_COORDINATOR,
            UserRole::PGAM
        ]);
    }

    /**
     * Check if user can manage lecturers (Office Assistant, Program Coordinator, PGAM)
     */
    public function canManageLecturers(User $user): bool
    {
        return $user->hasAnyRole([
            UserRole::OFFICE_ASSISTANT,
            UserRole::PROGRAM_COORDINATOR,
            UserRole::PGAM
        ]);
    }

    /**
     * Get role-specific permissions based on FSES requirements
     */
    public function getRolePermissions(string $roleName): array
    {
        $permissions = [
            UserRole::OFFICE_ASSISTANT => [
                'students' => ['view', 'create', 'edit', 'import'],
                'users' => ['view', 'create', 'edit'],
                'lecturers' => ['view', 'create', 'edit'],
                'programs' => ['view'],
            ],
            UserRole::SUPERVISOR => [
                'students' => ['view'],
                'evaluations' => ['view', 'nominate', 'modify'],
                'nominations' => ['view', 'create', 'edit', 'postpone'],
            ],
            UserRole::PROGRAM_COORDINATOR => [
                'students' => ['view', 'create', 'edit', 'delete'],
                'users' => ['view', 'create', 'edit', 'delete'],
                'lecturers' => ['view', 'create', 'edit', 'delete'],
                'programs' => ['view', 'create', 'edit', 'delete'],
                'evaluations' => ['view', 'assign', 'lock'],
                'nominations' => ['view', 'lock'],
                'chairpersons' => ['view', 'assign', 'modify'],
                'reports' => ['view', 'generate', 'download'],
            ],
            UserRole::CHAIRPERSON => [
                'students' => ['view'],
                'evaluations' => ['view', 'conduct'],
                'reports' => ['view'],
            ],
            UserRole::PGAM => [
                'students' => ['view', 'create', 'edit', 'delete'],
                'users' => ['view', 'create', 'edit', 'delete'],
                'lecturers' => ['view', 'create', 'edit', 'delete'],
                'programs' => ['view', 'create', 'edit', 'delete'],
                'evaluations' => ['view', 'assign', 'lock'],
                'nominations' => ['view', 'lock'],
                'chairpersons' => ['view', 'assign', 'modify'],
                'reports' => ['view', 'generate', 'download', 'publish'],
                'settings' => ['view', 'edit'],
            ],
        ];

        return $permissions[$roleName] ?? [];
    }

    /**
     * Validate if a user can perform an action on a specific resource
     */
    public function validateAction(User $user, string $module, string $action, $resource = null): bool
    {
        // Basic permission check
        if (!$user->hasPermissionFor($module, $action)) {
            return false;
        }

        // Role-specific validation rules
        switch ($module) {
            case 'students':
                return $this->validateStudentAction($user, $action, $resource);
            case 'evaluations':
                return $this->validateEvaluationAction($user, $action, $resource);
            case 'nominations':
                return $this->validateNominationAction($user, $action, $resource);
            default:
                return true;
        }
    }

    /**
     * Validate student-related actions
     */
    private function validateStudentAction(User $user, string $action, $student = null): bool
    {
        $role = $user->getPrimaryRoleName();

        switch ($role) {
            case UserRole::SUPERVISOR:
                // Supervisors can only view their assigned students
                if ($student && $action === 'view') {
                    return $student->main_supervisor_id === $user->lecturer_id;
                }
                return $action === 'view';
            
            case UserRole::PROGRAM_COORDINATOR:
                // Program Coordinators can manage students in their department
                if ($student && in_array($action, ['edit', 'delete'])) {
                    return $student->department === $user->department;
                }
                return true;
            
            case UserRole::PGAM:
                // PGAM can manage all students
                return true;
            
            case UserRole::OFFICE_ASSISTANT:
                // Office Assistants can manage all students
                return true;
            
            default:
                return false;
        }
    }

    /**
     * Validate evaluation-related actions
     */
    private function validateEvaluationAction(User $user, string $action, $evaluation = null): bool
    {
        $role = $user->getPrimaryRoleName();

        switch ($role) {
            case UserRole::SUPERVISOR:
                // Supervisors can only manage their students' evaluations
                if ($evaluation && in_array($action, ['nominate', 'modify'])) {
                    return $evaluation->student->main_supervisor_id === $user->lecturer_id;
                }
                return in_array($action, ['view', 'nominate', 'modify']);
            
            case UserRole::PROGRAM_COORDINATOR:
                // Program Coordinators can manage evaluations in their department
                if ($evaluation && in_array($action, ['assign', 'lock'])) {
                    return $evaluation->student->department === $user->department;
                }
                return in_array($action, ['view', 'assign', 'lock']);
            
            case UserRole::PGAM:
                // PGAM can manage all evaluations
                return true;
            
            default:
                return false;
        }
    }

    /**
     * Validate nomination-related actions
     */
    private function validateNominationAction(User $user, string $action, $nomination = null): bool
    {
        $role = $user->getPrimaryRoleName();

        switch ($role) {
            case UserRole::SUPERVISOR:
                // Supervisors can only manage their students' nominations
                if ($nomination && in_array($action, ['create', 'edit', 'postpone'])) {
                    return $nomination->student->main_supervisor_id === $user->lecturer_id;
                }
                return in_array($action, ['view', 'create', 'edit', 'postpone']);
            
            case UserRole::PROGRAM_COORDINATOR:
                // Program Coordinators can lock nominations in their department
                if ($nomination && $action === 'lock') {
                    return $nomination->student->department === $user->department;
                }
                return in_array($action, ['view', 'lock']);
            
            case UserRole::PGAM:
                // PGAM can manage all nominations
                return true;
            
            default:
                return false;
        }
    }
} 
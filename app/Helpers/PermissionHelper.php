<?php

namespace App\Helpers;

use App\Modules\UserManagement\Services\PermissionService;
use App\Modules\Auth\Models\User;
use App\Enums\UserRole;

trait PermissionHelper
{
    protected $permissionService;

    /**
     * Initialize permission service
     */
    protected function initPermissionService()
    {
        if (!$this->permissionService) {
            $this->permissionService = app(PermissionService::class);
        }
    }

    /**
     * Check if current user has permission for a module and action
     */
    protected function userCan(string $module, string $action): bool
    {
        $this->initPermissionService();
        $user = auth()->user();
        
        if (!$user) {
            return false;
        }

        return $this->permissionService->userCan($user, $module, $action);
    }

    /**
     * Check if current user has a specific role
     */
    protected function userHasRole(string $role): bool
    {
        $this->initPermissionService();
        $user = auth()->user();
        
        if (!$user) {
            return false;
        }

        return $this->permissionService->userHasSpecificRole($user, $role);
    }

    /**
     * Check if current user has any of the specified roles
     */
    protected function userHasAnyRole(array $roles): bool
    {
        $this->initPermissionService();
        $user = auth()->user();
        
        if (!$user) {
            return false;
        }

        return $this->permissionService->userHasRole($user, $roles);
    }

    /**
     * Get current user's accessible modules
     */
    protected function getUserAccessibleModules(): array
    {
        $this->initPermissionService();
        $user = auth()->user();
        
        if (!$user) {
            return [];
        }

        return $this->permissionService->getUserAccessibleModules($user);
    }

    /**
     * Check if current user can manage students
     */
    protected function canManageStudents(): bool
    {
        $this->initPermissionService();
        $user = auth()->user();
        
        if (!$user) {
            return false;
        }

        return $this->permissionService->canManageStudents($user);
    }

    /**
     * Check if current user can manage users
     */
    protected function canManageUsers(): bool
    {
        $this->initPermissionService();
        $user = auth()->user();
        
        if (!$user) {
            return false;
        }

        return $this->permissionService->canManageUsers($user);
    }

    /**
     * Check if current user can nominate examiners
     */
    protected function canNominateExaminers(): bool
    {
        $this->initPermissionService();
        $user = auth()->user();
        
        if (!$user) {
            return false;
        }

        return $this->permissionService->canNominateExaminers($user);
    }

    /**
     * Check if current user can assign chairpersons
     */
    protected function canAssignChairpersons(): bool
    {
        $this->initPermissionService();
        $user = auth()->user();
        
        if (!$user) {
            return false;
        }

        return $this->permissionService->canAssignChairpersons($user);
    }

    /**
     * Check if current user can lock nominations
     */
    protected function canLockNominations(): bool
    {
        $this->initPermissionService();
        $user = auth()->user();
        
        if (!$user) {
            return false;
        }

        return $this->permissionService->canLockNominations($user);
    }

    /**
     * Check if current user can view reports
     */
    protected function canViewReports(): bool
    {
        $this->initPermissionService();
        $user = auth()->user();
        
        if (!$user) {
            return false;
        }

        return $this->permissionService->canViewReports($user);
    }

    /**
     * Check if current user can manage programs
     */
    protected function canManagePrograms(): bool
    {
        $this->initPermissionService();
        $user = auth()->user();
        
        if (!$user) {
            return false;
        }

        return $this->permissionService->canManagePrograms($user);
    }

    /**
     * Check if current user can manage lecturers
     */
    protected function canManageLecturers(): bool
    {
        $this->initPermissionService();
        $user = auth()->user();
        
        if (!$user) {
            return false;
        }

        return $this->permissionService->canManageLecturers($user);
    }

    /**
     * Get current user's primary role
     */
    protected function getCurrentUserRole(): ?string
    {
        $user = auth()->user();
        
        if (!$user) {
            return null;
        }

        $roleHierarchy = [
            UserRole::PGAM,
            UserRole::PROGRAM_COORDINATOR,
            UserRole::CHAIRPERSON,
            UserRole::SUPERVISOR,
            UserRole::OFFICE_ASSISTANT
        ];

        foreach ($roleHierarchy as $roleName) {
            if ($this->userHasRole($roleName)) {
                return $roleName;
            }
        }
        
        return null;
    }

    /**
     * Check if current user is Office Assistant
     */
    protected function isOfficeAssistant(): bool
    {
        return $this->userHasRole(UserRole::OFFICE_ASSISTANT);
    }

    /**
     * Check if current user is Research Supervisor
     */
    protected function isSupervisor(): bool
    {
        return $this->userHasRole(UserRole::SUPERVISOR);
    }

    /**
     * Check if current user is Program Coordinator
     */
    protected function isProgramCoordinator(): bool
    {
        return $this->userHasRole(UserRole::PROGRAM_COORDINATOR);
    }

    /**
     * Check if current user is PGAM
     */
    protected function isPGAM(): bool
    {
        return $this->userHasRole(UserRole::PGAM);
    }

    /**
     * Check if current user is Chairperson
     */
    protected function isChairperson(): bool
    {
        return $this->userHasRole(UserRole::CHAIRPERSON);
    }

    /**
     * Check if current user is an administrator (Program Coordinator or PGAM)
     */
    protected function isAdministrator(): bool
    {
        return $this->userHasAnyRole([UserRole::PROGRAM_COORDINATOR, UserRole::PGAM]);
    }
} 
<?php

namespace App\Modules\UserManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\Auth\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'role_name',
        'description',
        'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    /**
     * Get users that have this role
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_roles', 'role_id', 'user_id')
                    ->withTimestamps();
    }

    /**
     * Check if role has a specific permission
     */
    public function hasPermission(string $permission): bool
    {
        if (!$this->permissions) {
            return false;
        }

        // Check if permission exists in any module
        foreach ($this->permissions as $module => $actions) {
            if (is_array($actions) && in_array($permission, $actions)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if role has permission for a specific module and action
     */
    public function can(string $module, string $action): bool
    {
        if (!$this->permissions || !isset($this->permissions[$module])) {
            return false;
        }

        return in_array($action, $this->permissions[$module]);
    }

    /**
     * Get all permissions for this role
     */
    public function getAllPermissions(): array
    {
        return $this->permissions ?? [];
    }

    /**
     * Get permissions for a specific module
     */
    public function getModulePermissions(string $module): array
    {
        return $this->permissions[$module] ?? [];
    }

    /**
     * Check if role has any permission for a module
     */
    public function hasModulePermission(string $module): bool
    {
        return isset($this->permissions[$module]) && !empty($this->permissions[$module]);
    }

    /**
     * Add permission to role
     */
    public function addPermission(string $module, string $action): void
    {
        if (!isset($this->permissions[$module])) {
            $this->permissions[$module] = [];
        }

        if (!in_array($action, $this->permissions[$module])) {
            $this->permissions[$module][] = $action;
            $this->save();
        }
    }

    /**
     * Remove permission from role
     */
    public function removePermission(string $module, string $action): void
    {
        if (isset($this->permissions[$module])) {
            $this->permissions[$module] = array_filter(
                $this->permissions[$module],
                fn($perm) => $perm !== $action
            );
            $this->save();
        }
    }

    /**
     * Set permissions for a module
     */
    public function setModulePermissions(string $module, array $actions): void
    {
        $this->permissions[$module] = $actions;
        $this->save();
    }

    /**
     * Clear all permissions for a module
     */
    public function clearModulePermissions(string $module): void
    {
        unset($this->permissions[$module]);
        $this->save();
    }

    /**
     * Scope to get roles by name
     */
    public function scopeByName($query, string $roleName)
    {
        return $query->where('role_name', $roleName);
    }

    /**
     * Scope to get roles that have permission for a module
     */
    public function scopeWithModulePermission($query, string $module)
    {
        return $query->whereJsonContains("permissions->{$module}", []);
    }

    /**
     * Get role by name (static method)
     */
    public static function findByName(string $roleName): ?self
    {
        return static::where('role_name', $roleName)->first();
    }

    /**
     * Get all role names
     */
    public static function getAllRoleNames(): array
    {
        return static::pluck('role_name')->toArray();
    }

    /**
     * Check if role name exists
     */
    public static function roleNameExists(string $roleName): bool
    {
        return static::where('role_name', $roleName)->exists();
    }
} 
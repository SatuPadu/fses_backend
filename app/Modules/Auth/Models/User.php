<?php

namespace App\Modules\Auth\Models;

use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\UserManagement\Models\Lecturer;
use App\Modules\UserManagement\Models\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'staff_number',
        'name',
        'email',
        'password',
        'department',
        'lecturer_id',
        'last_login',
        'password_reset_token',
        'password_reset_expiry',
        'is_active',
        'is_password_updated',
        'failed_login_attempts',
    ];

    protected $hidden = [
        'password',
        'password_reset_token',
        'remember_token',
    ];

    protected $casts = [
        'last_login' => 'datetime',
        'password_reset_expiry' => 'datetime',
        'is_active' => 'boolean',
        'is_password_updated' => 'boolean',
        'failed_login_attempts' => 'integer',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [
            'staff_number' => $this->staff_number,
            'department' => $this->department,
            'roles' => $this->roles->pluck('role_name')->toArray(),
        ];
    }

    // Make sure the password field works with JWT Auth
    public function getAuthPassword()
    {
        return $this->password;
    }

    /**
     * Get the user's lecturer profile
     */
    public function lecturer()
    {
        return $this->belongsTo(Lecturer::class, 'staff_number', 'staff_number');
    }

    /**
     * Get the user's roles
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id')
                    ->withTimestamps();
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('role_name', $roleName)->exists();
    }

    /**
     * Check if user has any of the specified roles
     */
    public function hasAnyRole(array $roleNames): bool
    {
        return $this->roles()->whereIn('role_name', $roleNames)->exists();
    }

    /**
     * Check if user has a specific permission
     */
    public function hasPermission(string $permission): bool
    {
        return $this->roles()->get()->some(function ($role) use ($permission) {
            return $role->hasPermission($permission);
        });
    }

    /**
     * Check if user has permission for a specific module and action
     */
    public function hasPermissionFor(string $module, string $action): bool
    {
        return $this->roles()->get()->some(function ($role) use ($module, $action) {
            return $role->can($module, $action);
        });
    }

    /**
     * Get all permissions for the user
     */
    public function getAllPermissions(): array
    {
        $permissions = [];
        
        foreach ($this->roles as $role) {
            $rolePermissions = $role->getAllPermissions();
            foreach ($rolePermissions as $module => $actions) {
                if (!isset($permissions[$module])) {
                    $permissions[$module] = [];
                }
                $permissions[$module] = array_merge($permissions[$module], $actions);
            }
        }

        // Remove duplicates
        foreach ($permissions as $module => &$actions) {
            $actions = array_unique($actions);
        }

        return $permissions;
    }

    /**
     * Get user's primary role (first role)
     */
    public function getPrimaryRole(): ?Role
    {
        return $this->roles()->first();
    }

    /**
     * Get user's primary role name
     */
    public function getPrimaryRoleName(): ?string
    {
        $primaryRole = $this->getPrimaryRole();
        return $primaryRole ? $primaryRole->role_name : null;
    }

    /**
     * Check if user account is locked
     */
    public function isLocked(): bool
    {
        return !$this->is_active;
    }

    /**
     * Check if user needs to update password
     */
    public function needsPasswordUpdate(): bool
    {
        return !$this->is_password_updated;
    }

    /**
     * Increment failed login attempts
     */
    public function incrementFailedLoginAttempts(): void
    {
        $this->increment('failed_login_attempts');
        
        // Lock account after 3 failed attempts
        if ($this->failed_login_attempts >= 3) {
            $this->is_active = false;
        }
        
        $this->save();
    }

    /**
     * Reset failed login attempts
     */
    public function resetFailedLoginAttempts(): void
    {
        $this->failed_login_attempts = 0;
        $this->is_active = true;
        $this->save();
    }

    /**
     * Update last login timestamp
     */
    public function updateLastLogin(): void
    {
        $this->last_login = now();
        $this->save();
    }

    /**
     * Set password reset token
     */
    public function setPasswordResetToken(string $token, int $expiryMinutes = 60): void
    {
        $this->password_reset_token = $token;
        $this->password_reset_expiry = now()->addMinutes($expiryMinutes);
        $this->save();
    }

    /**
     * Clear password reset token
     */
    public function clearPasswordResetToken(): void
    {
        $this->password_reset_token = null;
        $this->password_reset_expiry = null;
        $this->save();
    }

    /**
     * Check if password reset token is valid
     */
    public function isPasswordResetTokenValid(): bool
    {
        return $this->password_reset_token && 
               $this->password_reset_expiry && 
               $this->password_reset_expiry->isFuture();
    }

    /**
     * Scope to get only active users
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get users by department
     */
    public function scopeByDepartment($query, string $department)
    {
        return $query->where('department', $department);
    }

    /**
     * Scope to get users by role
     */
    public function scopeByRole($query, string $roleName)
    {
        return $query->whereHas('roles', function ($q) use ($roleName) {
            $q->where('role_name', $roleName);
        });
    }

    // Relationships for evaluations and logs (commented out until models are created)
    // public function nominatedEvaluations()
    // {
    //     return $this->hasMany(StudentEvaluation::class, 'nominated_by', 'id');
    // }

    // public function lockedEvaluations()
    // {
    //     return $this->hasMany(StudentEvaluation::class, 'locked_by', 'id');
    // }

    // public function logs()
    // {
    //     return $this->hasMany(Log::class, 'user_id', 'id');
    // }
}
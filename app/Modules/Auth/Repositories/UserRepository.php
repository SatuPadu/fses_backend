<?php

namespace App\Modules\Auth\Repositories;

use App\Modules\Auth\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class UserRepository
{
    /**
     * Create a new user.
     *
     * @param array $data
     * @return User
     * @throws QueryException
     */
    public function create(array $data): User
    {
        // Begin a transaction to ensure data consistency
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'staff_number' => $data['staff_number'],
                'department' => $data['department'],
                'password' => Hash::make($data['staff_number']),
                'is_active' => $data['is_active'] ?? true,
                'lecturer_id' => $data['lecturer_id'] ?? null,
            ]);

            // If roles are provided, assign them to the user
            if (!empty($data['roles'])) {
                $this->assignRoles($user, $data['roles']);
            }

            return $user;
        });
    }

    /**
     * Find a user by email.
     *
     * @param string $email
     * @return User|null
     */
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * Find a user by staff number.
     *
     * @param string $staffNumber
     * @return User|null
     */
    public function findByStaffNumber(string $staffNumber): ?User
    {
        return User::where('staff_number', $staffNumber)->first();
    }

    /**
     * Find a user by ID.
     *
     * @param int $id
     * @return User|null
     */
    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    /**
     * Find a user by password reset token.
     *
     * @param string $token
     * @return User|null
     */
    public function findByResetToken(string $token): ?User
    {
        return User::where('password_reset_token', $token)
            ->where('password_reset_expiry', '>', now())
            ->first();
    }

    /**
     * Update a user.
     *
     * @param User $user
     * @param array $data
     * @return User
     * @throws QueryException
     */
    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            // Update only the fields that are provided
            if (isset($data['name'])) {
                $user->name = $data['name'];
            }
            
            if (isset($data['email'])) {
                $user->email = $data['email'];
            }
            
            if (isset($data['staff_number'])) {
                $user->staff_number = $data['staff_number'];
            }
            
            if (isset($data['department'])) {
                $user->department = $data['department'];
            }
            
            if (isset($data['password'])) {
                $user->password = Hash::make($data['password']);
                $user->is_password_updated = true;
            }
            
            if (isset($data['is_active'])) {
                $user->is_active = $data['is_active'];
            }
            
            if (isset($data['lecturer_id'])) {
                $user->lecturer_id = $data['lecturer_id'];
            }
            
            $user->save();
            
            // Update roles if provided
            if (isset($data['roles'])) {
                $this->syncRoles($user, $data['roles']);
            }
            
            return $user;
        });
    }

    /**
     * Delete a user.
     *
     * @param User $user
     * @return bool
     * @throws \Exception
     */
    public function delete(User $user): bool
    {
        return DB::transaction(function () use ($user) {
            // Remove role associations first
            DB::table('user_roles')->where('user_id', $user->id)->delete();
            
            // Then delete the user
            return $user->delete();
        });
    }

    /**
     * Set password reset token for a user.
     *
     * @param User $user
     * @param string $token
     * @param int $expiryHours
     * @return User
     */
    public function setPasswordResetToken(User $user, string $token, int $expiryHours = 24): User
    {
        $user->password_reset_token = $token;
        $user->password_reset_expiry = now()->addHours($expiryHours);
        $user->save();
        
        return $user;
    }

    /**
     * Clear password reset token for a user.
     *
     * @param User $user
     * @return User
     */
    public function clearPasswordResetToken(User $user): User
    {
        $user->password_reset_token = null;
        $user->password_reset_expiry = null;
        $user->save();
        
        return $user;
    }

    /**
     * Get all users with pagination.
     *
     * @param int $perPage
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getAllPaginated(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = User::query();
        
        // Apply filters if provided
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('staff_number', 'like', "%{$search}%");
            });
        }
        
        if (isset($filters['department'])) {
            $query->where('department', $filters['department']);
        }
        
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }
        
        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get user's roles.
     *
     * @param User $user
     * @return array
     */
    public function getUserRoles(User $user): array
    {
        return DB::table('roles')
            ->join('user_roles', 'roles.id', '=', 'user_roles.role_id')
            ->where('user_roles.user_id', $user->id)
            ->select('roles.*')
            ->get()
            ->toArray();
    }

    /**
     * Assign roles to a user.
     *
     * @param User $user
     * @param array $roleIds
     * @return void
     */
    public function assignRoles(User $user, array $roleIds): void
    {
        $now = now();
        $records = [];
        
        foreach ($roleIds as $roleId) {
            $records[] = [
                'user_id' => $user->id,
                'role_id' => $roleId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        
        DB::table('user_roles')->insert($records);
    }

    /**
     * Sync roles for a user (remove existing and add new ones).
     *
     * @param User $user
     * @param array $roleIds
     * @return void
     */
    public function syncRoles(User $user, array $roleIds): void
    {
        DB::transaction(function () use ($user, $roleIds) {
            // Remove all existing roles
            DB::table('user_roles')->where('user_id', $user->id)->delete();
            
            // Assign new roles
            $this->assignRoles($user, $roleIds);
        });
    }
}
<?php

namespace App\Modules\Auth\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Modules\Auth\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthService
{

    /**
     * Authenticate user and generate JWT token
     *
     * @param array $credentials
     * @return array
     */
    public function login(array $credentials)
    {
        // Find user by email or staff number
        $user = User::where('email', $credentials['identity'])
            ->orWhere('staff_number', $credentials['identity'])
            ->first();

        if (!$user) {
            throw new \Exception('User not found.');
        }

        // Check if account is locked
        if (!$user->is_active) {
            throw new \Exception('Account is locked due to multiple failed login attempts with wrong password. Please contact administrator.');
        }

        // Verify password
        if (!Hash::check($credentials['password'], $user->password)) {
            $user->failed_login_attempts += 1;
            if ($user->failed_login_attempts > 2) {
                $user->is_active = false;
            }
            $user->save();

            if ($user->failed_login_attempts > 2) {
                throw new \Exception('Account is locked due to multiple failed login attempts with wrong password. Please contact administrator.');
            }
            throw new \Exception('Incorrect password.');
        }

        // Reset failed login attempts on successful login
        $user->failed_login_attempts = 0;
        $user->save();

        // Generate token
        $token = JWTAuth::fromUser($user);
        
        // Update last login
        $this->updateLastLogin($user);

        // Get user roles
        $roles = $user->roles;

        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::factory()->getTTL() * 60,
            'user' => $user,
            'roles' => $roles
        ];
    }

    /**
     * Update user's last login timestamp
     *
     * @param User $user
     * @return void
     */
    protected function updateLastLogin(User $user): void
    {
        $user->last_login = now();
        $user->save();
    }

    /**
     * Register a new user
     *
     * @param array $userData
     * @return array
     */
    public function register(array $userData): array
    {
        // Create user via repository
        $user = User::create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'staff_number' => $userData['staff_number'],
            'department' => $userData['department'],
            'password' => Hash::make($userData['password']),
            'is_active' => true,
            'roles' => $userData['roles'] ?? [],
        ]);

        return [
            'user' => $user,
            'roles' => $user->roles
        ];
    }

    /**
     * Logout and invalidate token
     *
     * @return bool
     */
    public function logout(): bool
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return true;
        } catch (JWTException $e) {
            return false;
        }
    }

    /**
     * Refresh authentication token
     *
     * @return array|false
     */
    public function refreshToken()
    {
        try {
            $token = JWTAuth::refresh(JWTAuth::getToken());
            $user = $this->getAuthenticatedUser();
            
            return [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => Auth::factory()->getTTL() * 60,
                'user' => $user,
                'roles' => $user ? $user->roles : []
            ];
        } catch (JWTException $e) {
            return false;
        }
    }

    /**
     * Get currently authenticated user
     *
     * @return User|null
     */
    public function getAuthenticatedUser()
    {
        try {
            return JWTAuth::parseToken()->authenticate();
        } catch (JWTException $e) {
            return null;
        }
    }

    /**
     * Reactivate a locked user account
     *
     * @param int $userId
     * @return array
     * @throws \Exception
     */
    public function reactivateAccount(int $userId): array
    {
        $user = User::find($userId);
        
        if (!$user) {
            throw new \Exception('User not found.');
        }

        $user->is_active = true;
        $user->failed_login_attempts = 0;
        $user->save();

        return [
            'user' => $user,
            'message' => 'Account reactivated successfully.'
        ];
    }
}
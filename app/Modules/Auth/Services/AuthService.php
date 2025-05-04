<?php

namespace App\Modules\Auth\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Modules\Auth\Models\User;
use App\Modules\Auth\Repositories\UserRepository;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthService
{
    protected $userRepository;
    
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

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

        
        
        // Verify password
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw new \Exception('Invalid credentials provided.');
        }

        // Generate token
        $token = JWTAuth::fromUser($user);
        
        // Update last login
        $this->updateLastLogin($user);

        // Get user roles
        $roles = $this->userRepository->getUserRoles($user);

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
        $user = $this->userRepository->create([
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
            'roles' => $this->userRepository->getUserRoles($user)
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
                'roles' => $user ? $this->userRepository->getUserRoles($user) : []
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
}
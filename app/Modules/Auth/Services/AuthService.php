<?php

namespace App\Modules\Auth\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Modules\Auth\Models\User;
use App\Modules\Auth\Repositories\UserRepository;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Str;

class AuthService
{
    protected $userRepository;
    
    /**
     * Create a new service instance.
     *
     * @param UserRepository $userRepository
     */
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Login a user and generate a JWT token
     *
     * @param array $credentials
     * @return array|false
     */
    public function login(array $credentials)
    {
        // Allow login with email or staff number
        $field = filter_var($credentials['identity'], FILTER_VALIDATE_EMAIL) ? 'email' : 'staff_number';
        
        $loginData = [
            $field => $credentials['identity'],
            'password' => $credentials['password']
        ];
        
        if (!Auth::attempt($loginData)) {
            return false;
        }

        $user = Auth::user();
        
        // Check if the user is active
        if (!$user->is_active) {
            Auth::logout();
            return false;
        }
        
        $this->updateLastLogin($user);

        $token = JWTAuth::fromUser($user);
        
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
     * Register a new user
     *
     * @param array $userData
     * @return array
     */
    public function register(array $userData): array
    {
        $user = $this->userRepository->create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'staff_number' => $userData['staff_number'],
            'department' => $userData['department'],
            'password' => $userData['password'],
            'is_password_updated' => true,
            'is_active' => true,
            'roles' => $userData['roles'] ?? [],
        ]);

        // You can add additional functionality here if needed
        // Such as sending welcome email, etc.

        return [
            'user' => $user,
            'roles' => $this->userRepository->getUserRoles($user)
        ];
    }

    /**
     * Update the user's last login timestamp
     *
     * @param User $user
     * @return void
     */
    public function updateLastLogin(User $user): void
    {
        $user->last_login = now();
        $user->save();
    }

    /**
     * Invalidate a user's token
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
     * Refresh a token
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
     * Get the currently authenticated user
     * 
     * @return User|null
     */
    public function getAuthenticatedUser()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            return $user;
        } catch (JWTException $e) {
            return null;
        }
    }
    
    /**
     * Initiate password reset
     *
     * @param string $email
     * @return array|false
     */
    public function initiatePasswordReset(string $email)
    {
        $user = $this->userRepository->findByEmail($email);
        
        if (!$user) {
            return false;
        }
        
        // Generate unique token
        $token = Str::random(60);
        
        // Save token and expiry time
        $this->userRepository->setPasswordResetToken($user, $token);
        
        return [
            'user' => $user,
            'token' => $token
        ];
    }
    
    /**
     * Reset password using token
     *
     * @param string $token
     * @param string $password
     * @return User|false
     */
    public function resetPassword(string $token, string $password)
    {
        $user = $this->userRepository->findByResetToken($token);
        
        if (!$user) {
            return false;
        }
        
        // Update password and clear reset token
        $this->userRepository->update($user, [
            'password' => $password,
            'is_password_updated' => true
        ]);
        
        $this->userRepository->clearPasswordResetToken($user);
        
        return $user;
    }
    
    /**
     * Change password for authenticated user
     *
     * @param string $currentPassword
     * @param string $newPassword
     * @return User|false
     */
    public function changePassword(string $currentPassword, string $newPassword)
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user || !Hash::check($currentPassword, $user->password)) {
            return false;
        }
        
        $this->userRepository->update($user, [
            'password' => $newPassword,
            'is_password_updated' => true
        ]);
        
        return $user;
    }
}
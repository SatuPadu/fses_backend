<?php

namespace App\Modules\Auth\Services;

use App\Modules\Auth\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * Register a new user and generate an API token.
     *
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function register(array $data): array
    {
        try {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            // Ensure HasApiTokens trait is used in the User model
            $token = $user->createToken('auth_token')->plainTextToken;

            return [
                'user' => $user,
                'token' => $token,
            ];
        } catch (\Exception $e) {
            // Log the exception for debugging
            logger()->error('User registration failed', ['exception' => $e]);
            throw new \Exception('User registration failed: ' . $e->getMessage());
        }
    }

    /**
     * Authenticate the user and generate an API token.
     *
     * @param array $credentials
     * @return array
     * @throws \Exception
     */
    public function login(array $credentials): array
    {
        if (!Auth::attempt($credentials)) {
            throw new \Exception('Invalid credentials provided.');
        }

        $user = Auth::user();

        if (!$user) {
            throw new \Exception('Authentication failed. User not found.');
        }

        return [
            'user' => $user,
        ];
    }

    /**
     * Logout the user by revoking all tokens.
     *
     * @param User $user
     * @return void
     * @throws \Exception
     */
    public function logout(User $user): void
    {
        try {
            // Ensure tokens relationship exists on the User model
            $user->tokens()->delete();
        } catch (\Exception $e) {
            // Log the exception for debugging
            logger()->error('Failed to log out', ['exception' => $e]);
            throw new \Exception('Failed to log out: ' . $e->getMessage());
        }
    }
}
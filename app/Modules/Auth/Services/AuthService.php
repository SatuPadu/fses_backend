<?php

namespace App\Modules\Auth\Services;

use App\Modules\Auth\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuthService
{
    /**
     * Register a new user.
     *
     * @param array $data
     * @return array
     */
    public function register(array $data): array
    {
        try {
            // Check if the email already exists
            if (User::where('email', $data['email'])->exists()) {
                throw ValidationException::withMessages([
                    'email' => ['A user with this email already exists.'],
                ]);
            }

            // Create the user in the database
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            // Generate an API token for the user
            $token = $user->createToken($user->email)->plainTextToken;

            return [
                'user' => $user,
                'token' => $token,
            ];
        } catch (ValidationException $e) {
            // Handle validation errors
            throw $e;
        } catch (QueryException $e) {
            // Handle database-related exceptions
            throw new HttpException(500, 'Failed to register user due to a database error.', $e);
        } catch (\Exception $e) {
            // Handle general exceptions
            throw new HttpException(500, 'An unexpected error occurred during registration.', $e);
        }
    }

    /**
     * Log in a user and return an API token.
     *
     * @param array $credentials
     * @return array|null
     * @throws ValidationException
     */
    public function login(array $credentials): ?array
    {
        // Find the user by email
        $user = User::where('email', $credentials['email'])->first();

        // Check if the user exists and if the password matches
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Generate an API token
        $token = $user->createToken($user->email)->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Log out the user and revoke the current token.
     *
     * @param User $user
     * @return void
     */
    public function logout(User $user): void
    {
        /** @var PersonalAccessToken|null $token */
        $token = $user->currentAccessToken();

        if ($token) {
            $token->delete(); // Revoke the current token
        }
    }

    /**
     * Retrieve the currently authenticated user.
     *
     * @return User|null
     */
    public function user(): ?User
    {
        return Auth::user();
    }
}
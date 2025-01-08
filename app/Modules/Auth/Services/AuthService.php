<?php

namespace App\Modules\Auth\Services;

use App\Modules\Auth\Models\User;
use App\Modules\Auth\Repositories\UserRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuthService
{
    protected UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Register a new user.
     *
     * @param array $data
     * @return array
     */
    public function register(array $data): array
    {
        try {
            if ($this->userRepository->findByEmail($data['email'])) {
                throw new HttpException(
                    409,
                    'The email has already been taken.'
                );
            }
            
            $user = $this->userRepository->create($data);

            if (!$user) {
                throw new HttpException(500, 'Failed to create user.');
            }

            $token = $user->createToken($user->email)->plainTextToken;

            if (!$token) {
                throw new HttpException(500, 'Failed to create user token.');
            }

            return [
                'user' => $user,
                'token' => $token,
            ];
        } catch (\Throwable $e) {
            throw new HttpException($e->getCode(), $e->getMessage(), $e);
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
        $user = $this->userRepository->findByEmail($credentials['email']);

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

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
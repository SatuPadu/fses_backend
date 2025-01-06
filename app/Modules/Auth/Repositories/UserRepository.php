<?php

namespace App\Modules\Auth\Repositories;

use App\Modules\Auth\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;

class UserRepository
{
    /**
     * Check if a user exists by email.
     *
     * @param string $email
     * @return bool
     */
    public function existsByEmail(string $email): bool
    {
        return User::where('email', $email)->exists();
    }

    /**
     * Create a new user.
     *
     * @param array $data
     * @return User
     * @throws QueryException
     */
    public function create(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
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
}
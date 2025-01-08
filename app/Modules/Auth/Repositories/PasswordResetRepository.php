<?php

namespace App\Modules\Auth\Repositories;

use Illuminate\Support\Facades\Hash;
use App\Modules\Auth\Models\PasswordReset;

class PasswordResetRepository
{
    public function store(string $email, string $hashedToken, \DateTime $expiresAt): void
    {
        PasswordReset::updateOrCreate(
            ['email' => $email],
            ['token' => $hashedToken, 'expires_at' => $expiresAt]
        );
    }

    public function findByToken(string $email, string $token): ?PasswordReset
    {
        $resetRecord = PasswordReset::where('email', $email)->orderBy("created_at", "DESC")->first();

        if ($resetRecord && Hash::check($token, $resetRecord->token)) {
            return $resetRecord;
        }

        return null;
    }

    public function delete(PasswordReset $resetRecord): void
    {
        $resetRecord->delete();
    }
}
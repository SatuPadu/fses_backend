<?php

namespace App\Modules\Auth\Services;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Modules\Auth\Repositories\PasswordResetRepository;
use App\Mail\PasswordResetMail;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Illuminate\Auth\Access\AuthorizationException;

class PasswordResetService
{
    private PasswordResetRepository $passwordResetRepo;

    public function __construct(PasswordResetRepository $passwordResetRepo)
    {
        $this->passwordResetRepo = $passwordResetRepo;
    }

    /**
     * Send password reset link to the user's email.
     *
     * @param string $email
     * @return string
     * @throws NotFoundHttpException
     */
    public function sendResetLink(string $email): string
    {
        // Validate email exists
        $user = User::where('email', $email)->first();
        if (!$user) {
            throw new NotFoundHttpException('Invalid email address.');
        }

        // Generate token and expiry time
        $token = Str::random(64); // Unique token
        $hashedToken = Hash::make($token); // Hashed for security
        $expiresAt = Carbon::now()->addMinutes(config('auth.passwords.users.expire', 60));

        // Store token in repository
        $this->passwordResetRepo->store($email, $hashedToken, $expiresAt);

        // Send reset email asynchronously
        Mail::to($email)->queue(new PasswordResetMail($token));
        
        return $token;
    }

    /**
     * Reset the user's password.
     *
     * @param array $data
     * @return void
     * @throws NotFoundHttpException
     * @throws AuthorizationException
     * @throws UnprocessableEntityHttpException
     */
    public function resetPassword(array $data): void
    {
        // Find reset record by email and token
        $resetRecord = $this->passwordResetRepo->findByToken($data['email'], $data['token']);
        if (!$resetRecord) {
            throw new NotFoundHttpException('Invalid or expired token.');
        }

        // Check token expiration
        if (Carbon::now()->greaterThan($resetRecord->expires_at)) {
            $this->passwordResetRepo->delete($resetRecord); // Cleanup expired token
            throw new AuthorizationException('The password reset token has expired.');
        }

        // Check if the new password matches the old password
        $user = User::where('email', $data['email'])->firstOrFail();
        if (Hash::check($data['password'], $user->password)) {
            throw new UnprocessableEntityHttpException('The new password cannot be the same as the old password.');
        }

        // Update user's password
        $user->update(['password' => Hash::make($data['password'])]);

        // Delete reset token to prevent reuse
        $this->passwordResetRepo->delete($resetRecord);
    }
}
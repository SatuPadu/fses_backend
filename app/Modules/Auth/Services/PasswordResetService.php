<?php

namespace App\Modules\Auth\Services;

use Carbon\Carbon;
use App\Modules\Auth\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordResetMail;
use App\Modules\Auth\Models\PasswordReset;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PasswordResetService
{
    /**
     * Send password reset link to the user's email.
     *
     * @param string $email
     * @return string
     * @throws NotFoundHttpException
     */
    public function sendResetLink(string $email): void
    {
        try {
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
            PasswordReset::updateOrCreate(
                ['email' => $email],
                ['token' => $hashedToken,
                'expires_at' => $expiresAt]
            );
    
            // Send reset email asynchronously
            Mail::to($email)->queue(new PasswordResetMail($hashedToken));
            
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Reset the user's password.
     *
     * @param array $data
     * @return void
     */
    public function resetPassword(array $data): void
    {
        // Find reset record by token
        $resetRecord = PasswordReset::where('token', $data['token'])->first();
        if (!$resetRecord) {
            throw new HttpException(400, 'Invalid or expired token.');
        }

        // Check token expiration
        if (Carbon::now()->greaterThan($resetRecord->expires_at)) {
            $resetRecord->delete(); // Cleanup expired token
            throw new HttpException(403, 'The password reset token has expired.');
        }

        // Find user by email
        $user = User::where('email', $resetRecord->email)->first();
        if (!$user) {
            throw new HttpException(404, 'User not found.');
        }

        // Check if the new password matches the old password
        if (Hash::check($data['password'], $user->password)) {
            throw new HttpException(422, 'The new password cannot be the same as the old password.');
        }

        // Update user's password
        $user->password = Hash::make($data['password']);
        $user->save();

        // Delete reset token to prevent reuse
        $resetRecord->delete();
    }

    /**
     * Set a new password for an authenticated user.
     *
     * @param mixed $user
     * @param string $newPassword
     * @return array
     */
    public function setNewPassword($user, string $newPassword): array
    {
        // Check if the new password matches the old password
        if (Hash::check($newPassword, $user->password)) {
            throw new HttpException(422, 'The new password cannot be the same as the old password.');
        }

        // Update user's password and mark as updated
        $user->update([
            'password' => Hash::make($newPassword),
            'is_password_updated' => true
        ]);

        // Generate a new JWT token
        $token = Auth::login($user);

        return [
            'access_token' => $token,
        ];
    }
}
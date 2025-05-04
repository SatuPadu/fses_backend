<?php

namespace App\Modules\Auth\Services;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordResetMail;
use App\Modules\Auth\Repositories\UserRepository;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Modules\Auth\Repositories\PasswordResetRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PasswordResetService
{
    private PasswordResetRepository $passwordResetRepo;
    private UserRepository $userRepository;

    public function __construct(
        PasswordResetRepository $passwordResetRepo,
        UserRepository $userRepository
    ) {
        $this->passwordResetRepo = $passwordResetRepo;
        $this->userRepository = $userRepository;
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
            $this->passwordResetRepo->store($email, $hashedToken, $expiresAt);
    
            // Send reset email asynchronously
            Mail::to($email)->queue(new PasswordResetMail($token));
            
            return $token;
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
        // Find reset record by email and token
        $resetRecord = $this->passwordResetRepo->findByToken($data['email'], $data['token']);
        if (!$resetRecord) {
            throw new HttpException(400, 'Invalid or expired token.');
        }

        // Check token expiration
        if (Carbon::now()->greaterThan($resetRecord->expires_at)) {
            $this->passwordResetRepo->delete($resetRecord); // Cleanup expired token
            throw new HttpException(403, 'The password reset token has expired.');
        }

        // Find user via UserRepository
        $user = $this->userRepository->findByEmail($data['email']);
        if (!$user) {
            throw new HttpException(404, 'User not found.');
        }

        // Check if the new password matches the old password
        if (Hash::check($data['password'], $user->password)) {
            throw new HttpException(422, 'The new password cannot be the same as the old password.');
        }

        // Update user's password
        $user->update(['password' => Hash::make($data['password'])]);

        // Delete reset token to prevent reuse
        $this->passwordResetRepo->delete($resetRecord);
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
<?php

namespace App\Modules\Auth\Requests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Modules\Auth\Models\User;

class ForgotPasswordRequest
{
    /**
     * Validate the forgot password request.
     *
     * @param Request $request
     * @return array Validated data
     *
     * @throws ValidationException
     */
    public static function validate(Request $request): array
    {
        // Perform the validation
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|exists:users,email',
        ], [
            'email.exists' => 'We could not find a user with that email address.',
        ]);

        // Add custom validation logic
        $validator->after(function ($validator) use ($request) {
            $email = $request->input('email');
            
            // Check if the account is active
            if (!self::isActiveAccount($email)) {
                $validator->errors()->add('email', 'This account has been deactivated. Please contact support.');
            }
            
            // Check if a reset token was recently created (optional - prevent request flooding)
            if (self::hasRecentResetToken($email)) {
                $validator->errors()->add('email', 'A password reset link has already been sent. Please check your email or try again later.');
            }
        });

        // Throw a ValidationException if validation fails
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Return the validated data
        return $validator->validated();
    }
    
    /**
     * Check if the user account is active.
     *
     * @param string|null $email
     * @return bool
     */
    protected static function isActiveAccount(?string $email): bool
    {
        if (!$email) {
            return false;
        }
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            return false;
        }
        
        return $user->is_active;
    }
    
    /**
     * Check if a reset token was recently created.
     *
     * @param string|null $email
     * @return bool
     */
    protected static function hasRecentResetToken(?string $email): bool
    {
        if (!$email) {
            return false;
        }
        
        $user = User::where('email', $email)->first();
        
        if (!$user || !$user->password_reset_expiry) {
            return false;
        }
        
        // Check if the token was created less than 15 minutes ago
        return $user->password_reset_expiry > now()->subMinutes(15);
    }
}
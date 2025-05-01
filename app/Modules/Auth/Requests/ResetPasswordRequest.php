<?php

namespace App\Modules\Auth\Requests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Modules\Auth\Models\User;
use Illuminate\Support\Facades\Hash;

class ResetPasswordRequest
{
    /**
     * Validate the reset password request.
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
            'token' => 'required|string',
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'email.exists' => 'We could not find a user with that email address.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.confirmed' => 'The password confirmation does not match.',
        ]);

        // Add custom validation logic
        $validator->after(function ($validator) use ($request) {
            $email = $request->input('email');
            $token = $request->input('token');
            $password = $request->input('password');
            
            // Get user by email
            $user = User::where('email', $email)->first();
            
            if ($user) {
                // Check if the token is valid and not expired
                if (!self::isValidToken($user, $token)) {
                    $validator->errors()->add('token', 'The password reset token is invalid or has expired.');
                }
                
                // Check if the account is active
                if (!$user->is_active) {
                    $validator->errors()->add('email', 'This account has been deactivated. Please contact support.');
                }
                
                // Check if new password is different from current password
                if (self::isSamePassword($user, $password)) {
                    $validator->errors()->add('password', 'The new password cannot be the same as your current password.');
                }
                
                // Check password complexity requirements
                if (!self::isPasswordComplex($password)) {
                    $validator->errors()->add('password', 'The password must include at least one uppercase letter, one lowercase letter, one number, and one special character.');
                }
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
     * Check if the reset token is valid and not expired.
     *
     * @param User $user
     * @param string $token
     * @return bool
     */
    protected static function isValidToken(User $user, string $token): bool
    {
        // Check if token matches stored token
        if ($user->password_reset_token !== $token) {
            return false;
        }
        
        // Check if token has expired
        if (!$user->password_reset_expiry || $user->password_reset_expiry < now()) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if the new password is the same as the current password.
     *
     * @param User $user
     * @param string $newPassword
     * @return bool
     */
    protected static function isSamePassword(User $user, string $newPassword): bool
    {
        return Hash::check($newPassword, $user->password);
    }
    
    /**
     * Check if password meets complexity requirements.
     *
     * @param string $password
     * @return bool
     */
    protected static function isPasswordComplex(string $password): bool
    {
        // Check for at least one uppercase letter
        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }
        
        // Check for at least one lowercase letter
        if (!preg_match('/[a-z]/', $password)) {
            return false;
        }
        
        // Check for at least one number
        if (!preg_match('/[0-9]/', $password)) {
            return false;
        }
        
        // Check for at least one special character
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            return false;
        }
        
        return true;
    }
}
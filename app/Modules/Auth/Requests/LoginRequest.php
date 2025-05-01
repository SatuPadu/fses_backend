<?php

namespace App\Modules\Auth\Requests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Modules\Auth\Models\User;

class LoginRequest
{
    /**
     * Validate the login data from the Request object.
     *
     * @param Request $request
     * @return array Validated data
     *
     * @throws ValidationException
     */
    public static function validate(Request $request): array
    {
        // Perform the validation for identity (either email or staff_number)
        $validator = Validator::make($request->all(), [
            'identity' => 'required|string',
            'password' => 'required|string|min:8',
        ]);

        // Add custom validation logic
        $validator->after(function ($validator) use ($request) {
            $identity = $request->input('identity');
            $isEmail = filter_var($identity, FILTER_VALIDATE_EMAIL);
            
            // Check for invalid email domains if identity is an email
            if ($isEmail && self::isInvalidEmailDomain($identity)) {
                $validator->errors()->add('identity', 'The email domain is not allowed.');
            }
            
            // Check if account is active
            if (!self::isActiveAccount($identity, $isEmail)) {
                $validator->errors()->add('identity', 'This account has been deactivated. Please contact support.');
            }
            
            // Check for too many login attempts (optional)
            // if (self::hasTooManyLoginAttempts($request)) {
            //     $validator->errors()->add('identity', 'Too many login attempts. Please try again later.');
            // }
        });

        // Throw a ValidationException if validation fails
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Return the validated data
        return $validator->validated();
    }

    /**
     * Check if the email domain is invalid.
     *
     * @param string|null $email
     * @return bool
     */
    protected static function isInvalidEmailDomain(?string $email): bool
    {
        if (!$email) {
            return false;
        }
        
        $invalidDomains = ['example.com', 'test.com'];
        $emailDomain = substr(strrchr($email, '@'), 1);

        return in_array($emailDomain, $invalidDomains);
    }
    
    /**
     * Check if the user account is active.
     *
     * @param string|null $identity Either email or staff_number
     * @param bool $isEmail Whether the identity is an email
     * @return bool
     */
    protected static function isActiveAccount(?string $identity, bool $isEmail): bool
    {
        if (!$identity) {
            return false;
        }
        
        // Query user based on whether identity is email or staff_number
        $query = User::query();
        if ($isEmail) {
            $query->where('email', $identity);
        } else {
            $query->where('staff_number', $identity);
        }
        
        $user = $query->first();
        
        // If user doesn't exist, we'll let the authentication process handle that error
        if (!$user) {
            return true;
        }
        
        // Check if user account is active
        return $user->is_active;
    }
    
    /**
     * Check if there are too many login attempts.
     *
     * @param Request $request
     * @return bool
     */
    protected static function hasTooManyLoginAttempts(Request $request): bool
    {
        // Implement rate limiting logic if needed
        // This is just a placeholder for implementing your own rate limiting
        
        return false;
    }
}
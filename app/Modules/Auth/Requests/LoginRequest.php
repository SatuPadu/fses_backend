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
        // Perform the validation for identity (either identity or staff_number)
        $validator = Validator::make($request->all(), [
            'identity' => 'required|string',
            'password' => 'required|string|min:8',
        ]);

        // Throw a ValidationException if validation fails
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Return the validated data
        return $validator->validated();
    }

    /**
     * Check if the identity domain is invalid.
     *
     * @param string|null $identity
     * @return bool
     */
    protected static function isInvalidEmailDomain(?string $identity): bool
    {
        if (!$identity) {
            return false;
        }
        
        $invalidDomains = ['example.com', 'test.com'];
        $emailDomain = substr(strrchr($identity, '@'), 1);

        return in_array($emailDomain, $invalidDomains);
    }
    
    /**
     * Check if the user account is active.
     *
     * @param string|null $identity Either identity or staff_number
     * @param bool $isEmail Whether the identity is an identity
     * @return bool
     */
    protected static function isActiveAccount(?string $identity, bool $isEmail): bool
    {
        if (!$identity) {
            return false;
        }
        
        // Query user based on whether identity is identity or staff_number
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
        
        return false;
    }
}
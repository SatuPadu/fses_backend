<?php

namespace App\Modules\Auth\Requests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RegisterRequest
{
    /**
     * Validate the registration data from the Request object.
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
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Add any custom validation logic here (if needed)
        $validator->after(function ($validator) use ($request) {
            if (self::isInvalidEmailDomain($request->input('email'))) {
                $validator->errors()->add('email', 'The email domain is not allowed.');
            }
        });

        // Throw a ValidationException if validation fails
        if ($validator->fails()) {
            // Ensure the error response includes password_confirmation if password doesn't match
            if (!isset($request->password_confirmation)) {
                $validator->errors()->add('password_confirmation', 'The password confirmation field is required.');
            } elseif ($request->password !== $request->password_confirmation) {
                $validator->errors()->add('password_confirmation', 'The password confirmation does not match.');
            }

            throw new ValidationException($validator, response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'data' => $validator->errors(),
            ], 422));
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
        $invalidDomains = ['example.com', 'test.com']; // List of invalid domains
        $emailDomain = $email ? substr(strrchr($email, '@'), 1) : null;

        return $emailDomain && in_array($emailDomain, $invalidDomains);
    }
}
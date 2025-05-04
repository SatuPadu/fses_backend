<?php

namespace App\Modules\Auth\Requests;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SetNewPasswordRequest
{
    /**
     * Validate the set new password request.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     * @throws ValidationException
     */
    public static function validate($request): array
    {
        // Define validation rules
        $validator = Validator::make($request->all(), [
            'password' => [
                'required',
                'string',
                'min:8',                       // Minimum 8 characters
                'confirmed',                   // Requires a password_confirmation field
                'different:old_password',      // Cannot be the same as the old password
                'regex:/^[a-zA-Z0-9]+$/',      // Only alphanumeric characters allowed
            ],
            'password_confirmation' => 'required|same:password'
        ], [
            'password.min' => 'The password must be at least 8 characters long.',
            'password.regex' => 'The password must contain only letters and numbers.',
            'password.different' => 'The new password cannot be the same as the old password.',
            'password_confirmation.same' => 'The password confirmation does not match.'
        ]);

        // Throw validation exception if validation fails
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Return validated data
        return $validator->validated();
    }
}
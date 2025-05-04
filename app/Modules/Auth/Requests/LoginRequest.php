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
}
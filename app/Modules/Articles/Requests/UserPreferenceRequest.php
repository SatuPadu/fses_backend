<?php

namespace App\Modules\Articles\Requests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UserPreferenceRequest
{
    /**
     * Validate the user preference data from the Request object.
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
            'topics' => 'nullable|array',
            'topics.*' => 'string|max:255',
            'sources' => 'nullable|array',
            'sources.*' => 'string|max:255',
            'authors' => 'nullable|array',
            'authors.*' => 'string|max:255',
        ]);

        // Throw a ValidationException if validation fails
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Return the validated data
        return $validator->validated();
    }
}
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
        $rules = self::rules();
        $messages = self::messages();

        // Perform the validation
        $validator = Validator::make($request->all(), $rules, $messages);

        // Throw a ValidationException if validation fails
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Return the validated data
        return $validator->validated();
    }

    /**
     * Define the validation rules for user preferences.
     *
     * @return array
     */
    public static function rules(): array
    {
        return [
            'topics' => 'nullable|array',
            'topics.*' => 'string|max:255',
            'sources' => 'nullable|array',
            'sources.*' => 'string|max:255',
            'authors' => 'nullable|array',
            'authors.*' => 'string|max:255',
        ];
    }

    /**
     * Define custom validation messages for user preferences.
     *
     * @return array
     */
    public static function messages(): array
    {
        return [
            'topics.array' => 'The topics field must be an array.',
            'topics.*.string' => 'Each topic must be a valid string.',
            'topics.*.max' => 'Each topic must not exceed 255 characters.',
            'sources.array' => 'The sources field must be an array.',
            'sources.*.string' => 'Each source must be a valid string.',
            'sources.*.max' => 'Each source must not exceed 255 characters.',
            'authors.array' => 'The authors field must be an array.',
            'authors.*.string' => 'Each author must be a valid string.',
            'authors.*.max' => 'Each author must not exceed 255 characters.',
        ];
    }
}
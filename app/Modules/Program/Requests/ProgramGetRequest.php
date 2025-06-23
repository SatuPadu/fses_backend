<?php

namespace App\Modules\Program\Requests;

use App\Enums\Department;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ProgramGetRequest
{
    /**
     * Validate the program request data from the Request object.
     *
     * @param Request $request
     * @return array Validated data
     *
     * @throws ValidationException
     */
    public static function validate(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'program_name' => ['nullable', 'string'],
            'program_code' => ['nullable', 'string'],
            'department' => ['nullable', Rule::in(Department::all())],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'all' => ['nullable', 'string', 'in:true,false']
        ]);

        if($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
} 
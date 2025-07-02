<?php

namespace App\Modules\Program\Requests;

use App\Enums\Department;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Enums\ProgramName;

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
            'program_name' => ['nullable', 'string', function ($attribute, $value, $fail) {
                $trimmedValue = trim($value);
                
                // Map full program names to enum values for validation
                $programNameMapping = [
                    'Doctor of Philosophy' => \App\Enums\ProgramName::PHD,
                    'Master of Philosophy' => \App\Enums\ProgramName::MPHIL,
                    'Doctor of Software Engineering' => \App\Enums\ProgramName::DSE
                ];
                
                $enumProgramName = $programNameMapping[$trimmedValue] ?? $trimmedValue;
                
                if (!\App\Enums\ProgramName::isValid($enumProgramName)) {
                    $fail('The program name must be one of: Doctor of Philosophy, Master of Philosophy, Doctor of Software Engineering, or the short forms: ' . implode(', ', \App\Enums\ProgramName::all()));
                }
            }],
            'program_code' => ['nullable', 'string'],
            'department' => ['nullable', Rule::in(Department::all())],
            'per_page' => ['nullable', 'integer'],
            'all' => ['nullable', 'string', 'in:true,false']
        ]);

        if($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
} 
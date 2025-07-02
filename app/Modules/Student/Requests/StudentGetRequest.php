<?php

namespace App\Modules\Student\Requests;

use App\Enums\Department;
use App\Enums\UserRole;
use App\Enums\EvaluationType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StudentGetRequest
{
    /**
     * Validate the student request data from the Request object.
     *
     * @param Request $request
     * @return array Validated data
     *
     * @throws ValidationException
     */
    public static function validate(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'matric_number' => ['nullable', 'string'],
            'name' => ['nullable', 'string'],
            'department' => ['nullable', Rule::in(Department::all())],
            'program' => ['nullable', 'integer', 'exists:programs,id'],
            'evaluation_type' => ['nullable', Rule::in(EvaluationType::all())],
            'is_postponed' => ['nullable', 'boolean'],
            'semester' => ['nullable', 'string'],
            'academic_year' => ['nullable', 'string'],
            'supervisor_id' => ['nullable', 'integer', 'exists:lecturers,id'],
            'coordinator_id' => ['nullable', 'integer', 'exists:users,id'],
            'per_page' => ['nullable', 'integer'],
            'with_evaluation' => ['nullable', 'string', 'in:true,false,0,1'],
            'my_role' => ['nullable', 'string', Rule::in(UserRole::all())],
            'email' => ['nullable', 'email'],
        ]);

        if($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
} 
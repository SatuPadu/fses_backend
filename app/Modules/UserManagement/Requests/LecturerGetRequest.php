<?php

namespace App\Modules\UserManagement\Requests;

use App\Enums\Department;
use App\Enums\LecturerTitle;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LecturerGetRequest
{
    /**
     * Validate the lecturer request data from the Request object.
     *
     * @param Request $request
     * @return array Validated data
     *
     * @throws ValidationException
     */
    public static function validate(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'staff_number' => ['nullable'],
            'name' => ['nullable'],
            'title' => ['nullable', Rule::in(LecturerTitle::all())],
            'email' => ['nullable'],
            'department' => ['nullable', Rule::in(Department::all())],
            'external_institution' => ['nullable'],
            'specialization' => ['nullable'],
            'phone' => ['nullable'],
            'per_page' => ['nullable', 'integer']
        ]);

        if($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}
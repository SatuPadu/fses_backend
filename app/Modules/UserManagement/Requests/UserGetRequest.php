<?php

namespace App\Modules\UserManagement\Requests;

use App\Enums\Department;
use App\Enums\LecturerTitle;
use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UserGetRequest
{
    /**
     * Validate the user request data from the Request object.
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
            'is_active' => ['nullable', 'boolean'],
            'is_password_updated' => ['nullable', 'boolean'],
            'role' => ['nullable', Rule::in(UserRole::all())],
            'per_page' => ['nullable', 'integer']
        ]);

        if($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}
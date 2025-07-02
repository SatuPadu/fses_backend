<?php

namespace App\Modules\UserManagement\Requests;

use App\Enums\Department;
use App\Enums\LecturerTitle;
use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UserCreateRequest
{
    /**
     * Validate the user request data from the Request object.
     *
     * @param Request $request
     * @param int $id = null
     * @return array Validated data
     *
     * @throws ValidationException
     */
    public static function validate(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'staff_number' => ['required', 'unique:users'],
            'name' => ['required'],
            'title' => ['required', Rule::in(LecturerTitle::all())],
            'email' => ['required', 'unique:users'],
            'department' => ['required', Rule::in(Department::all())],
            'phone' => ['nullable'],
            'specialization' => ['nullable'],
            'role' => ['required', Rule::in(UserRole::all())]
        ]);

        if($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}
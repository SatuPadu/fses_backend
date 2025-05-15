<?php

namespace App\Modules\UserManagement\Requests;

use App\Enums\Department;
use App\Enums\LecturerTitle;
use App\Modules\Auth\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UserCreateRequest
{
    /**
     * Validate the user/lecturer request data from the Request object.
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
            'staff_number' => ['required', 'unique:lecturers'],
            'name' => ['required'],
            'title' => ['required', Rule::in(LecturerTitle::all())],
            'email' => ['required', 'unique:lecturers'],
            'department' => ['required', Rule::in(Department::all())],
            'external_institution' => ['nullable'],
            'specialization' => ['nullable'],
            'phone' => ['nullable']
        ]);

        if($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}
<?php

namespace App\Modules\UserManagement\Requests;

use App\Enums\Department;
use App\Enums\LecturerTitle;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UserUpdateRequest
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
    public static function validate(Request $request, $id): array
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'title' => ['required', Rule::in(LecturerTitle::all())],
            'email' => ['required', Rule::unique('users')->ignore($id)],
            'department' => ['required', Rule::in(Department::all())],
            'phone' => ['nullable'],
            'external_institution' => ['nullable'],
            'specialization' => ['nullable']
        ]);

        if($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}
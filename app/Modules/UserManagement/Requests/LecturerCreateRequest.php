<?php

namespace App\Modules\UserManagement\Requests;

use App\Enums\Department;
use App\Enums\LecturerTitle;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LecturerCreateRequest
{
    /**
     * Validate the lecturer request data from the Request object.
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
            'staff_number' => ['nullable', 'unique:lecturers', 'min:8', 'alpha_num'],
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
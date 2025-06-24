<?php

namespace App\Modules\UserManagement\Requests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CheckPermissionRequest
{
    /**
     * Validate the check permission request data from the Request object.
     *
     * @param Request $request
     * @return array Validated data
     *
     * @throws ValidationException
     */
    public static function validate(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'module' => ['required', 'string'],
            'action' => ['required', 'string']
        ]);

        if($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
} 
<?php

namespace App\Modules\Evaluation\Requests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LockNominationsRequest
{
    public static function validate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'evaluation_ids' => ['required', 'array'],
            'evaluation_ids.*' => ['required', 'exists:student_evaluations,id'],
        ]);

        if($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
} 
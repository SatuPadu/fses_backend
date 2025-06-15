<?php

namespace App\Modules\Evaluation\Requests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UpdateAssignmentRequest
{
    public static function validate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            '*' => 'required|array',
            '*.evaluation_id' => 'required|integer|exists:student_evaluations,id',
            '*.chairperson_id' => 'required|integer|exists:lecturers,id',
            '*.is_auto_assigned' => 'required|boolean',
        ]);

        if($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}
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
            'assignments' => 'required|array',
            'assignments.*.evaluation_id' => 'required|integer|exists:student_evaluations,id',
            'assignments.*.chairperson_id' => 'required|integer|exists:lecturers,id,is_from_fai,1',
            'assignments.*.is_auto_assigned' => 'required|boolean',
        ]);

        if($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}
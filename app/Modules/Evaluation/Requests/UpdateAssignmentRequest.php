<?php

namespace App\Modules\Evaluation\Requests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UpdateAssignmentRequest
{
    public static function validate(Request $request, $evaluationId = null)
    {
        // If evaluationId is provided, it's a single update
        if ($evaluationId !== null) {
            $validator = Validator::make($request->all(), [
                'chairperson_id' => 'required|integer|exists:lecturers,id',
                'is_auto_assigned' => 'required|boolean',
                'semester' => 'nullable|integer',
                'academic_year' => 'nullable|string',
            ]);

            if($validator->fails()) {
                throw new ValidationException($validator);
            }

            $validated = $validator->validated();
            $validated['evaluation_id'] = $evaluationId;
            
            return $validated;
        }

        // Otherwise, it's a bulk update
        $validator = Validator::make($request->all(), [
            '*' => 'required|array',
            '*.evaluation_id' => 'required|integer|exists:student_evaluations,id',
            '*.chairperson_id' => 'required|integer|exists:lecturers,id',
            '*.is_auto_assigned' => 'required|boolean',
            '*.semester' => 'nullable|integer',
            '*.academic_year' => 'nullable|string',
        ]);

        if($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}
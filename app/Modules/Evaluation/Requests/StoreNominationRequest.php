<?php

namespace App\Modules\Evaluation\Requests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoreNominationRequest
{
    public static function validate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => ['required', 'exists:students,id'],
            'semester' => ['required', 'integer', 'min:1'],
            'academic_year' => ['required', 'string'],
            'research_title' => ['nullable', 'string', 'max:500'],
            'examiner1_id' => ['nullable', 'exists:lecturers,id', 'different:examiner2_id', 'different:examiner3_id'],
            'examiner2_id' => ['nullable', 'exists:lecturers,id', 'different:examiner1_id', 'different:examiner3_id'],
            'examiner3_id' => ['nullable', 'exists:lecturers,id', 'different:examiner1_id', 'different:examiner2_id'],
        ]);

        if($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}

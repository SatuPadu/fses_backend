<?php

namespace App\Modules\Evaluation\Requests;

use App\Enums\EvaluationType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UpdateNominationRequest
{
    public static function validate(Request $request, $id)
    {
        $data = $request->all();
        $data['evaluation_id'] = $id;

        $validator = Validator::make($data, [
            'evaluation_id' => ['required', 'exists:student_evaluations,id'],
            'semester' => ['nullable', 'integer', 'min:1'],
            'academic_year' => ['nullable', 'string'],
            'evaluation_type' => ['required', 'string', Rule::in(EvaluationType::all())],
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
<?php

namespace App\Modules\Evaluation\Requests;

use Illuminate\Http\Request;
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
            'examiner1_id' => ['nullable', 'exists:lecturers,id', 'different:examiner2_id', 'different:examiner3_id'],
            'examiner2_id' => ['nullable', 'exists:lecturers,id', 'different:examiner1_id', 'different:examiner3_id'],
            'examiner3_id' => ['nullable', 'exists:lecturers,id', 'different:examiner1_id', 'different:examiner2_id'],
        ]);

        if($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    public function rules(): array
    {
        return [
            'semester' => ['sometimes', 'integer', 'min:1'],
            'academic_year' => ['sometimes', 'string'],
            'examiner1_id' => ['nullable', 'exists:lecturers,id'],
            'examiner2_id' => ['nullable', 'exists:lecturers,id'],
            'examiner3_id' => ['nullable', 'exists:lecturers,id'],
            'chairperson_id' => ['nullable', 'exists:lecturers,id'],
            'is_auto_assigned' => ['boolean'],
        ];
    }
}
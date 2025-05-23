<?php

namespace App\Modules\Evaluation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Adjust authorization logic as needed
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
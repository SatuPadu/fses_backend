<?php

namespace App\Modules\Evaluation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Adjust authorization logic as needed
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'exists:students,id'],
            'semester' => ['required', 'integer', 'min:1'],
            'academic_year' => ['required', 'string'],
            'examiner1_id' => ['nullable', 'exists:lecturers,id'],
            'examiner2_id' => ['nullable', 'exists:lecturers,id'],
            'examiner3_id' => ['nullable', 'exists:lecturers,id'],
            'chairperson_id' => ['nullable', 'exists:lecturers,id'],
            'is_auto_assigned' => ['boolean'],
            'nominated_by' => ['nullable', 'exists:users,id'],
            'nominated_at' => ['nullable', 'date'],
        ];
    }
}
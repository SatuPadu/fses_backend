<?php

namespace App\Modules\Student\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Handles validation logic for updating an existing student.
 * Used in the StudentController@update method.
 */
class UpdateStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $studentId = $this->route('student') ?? $this->route('id');
        
        return [
            'matric_number' => 'sometimes|required|string|unique:students,matric_number,' . $studentId,
            'name' => 'sometimes|required|string',
            'email' => 'sometimes|required|email',
            'program_id' => 'sometimes|required|integer|exists:programs,id',
            'current_semester' => 'sometimes|required|string',
            'department' => 'sometimes|required|in:SEAT,II,BIHG,CAI,Other',
            'country' => 'nullable|string|max:100',
            'main_supervisor_id' => 'sometimes|required|integer|exists:lecturers,id',
            'evaluation_type' => 'sometimes|required|in:FirstEvaluation,ReEvaluation',
            'co_supervisor_id' => 'nullable|integer|exists:lecturers,id',
            'research_title' => 'nullable|string',
            'is_postponed' => 'boolean',
            'postponement_reason' => 'nullable|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'matric_number.unique' => 'The matric number has already been taken.',
            'program_id.exists' => 'The selected program is invalid.',
            'main_supervisor_id.exists' => 'The selected supervisor is invalid.',
            'co_supervisor_id.exists' => 'The selected co-supervisor is invalid.',
            'department.in' => 'The department must be one of: SEAT, II, BIHG, CAI, Other.',
            'evaluation_type.in' => 'The evaluation type must be either FirstEvaluation or ReEvaluation.',
        ];
    }
} 
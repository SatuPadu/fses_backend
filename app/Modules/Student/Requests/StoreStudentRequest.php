<?php

namespace App\Modules\Student\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Handles validation logic for storing a new student.
 * Used in the StudentController@store method.
 */
class StoreStudentRequest extends FormRequest
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
        return [
            'matric_number' => 'required|string|unique:students,matric_number',
            'name' => 'required|string',
            'email' => 'required|email',
            'program_id' => 'required|integer|exists:programs,id',
            'current_semester' => 'required|string',
            'department' => 'required|in:SEAT,II,BIHG,CAI,Other',
            'main_supervisor_id' => 'required|integer|exists:lecturers,id',
            'evaluation_type' => 'required|in:FirstEvaluation,ReEvaluation',
            'main_supervisor_id' => 'required|integer|exists:lecturers,id',
            'co_supervisor_id' => 'nullable|integer|exists:lecturers,id',
            'research_title' => 'nullable|string',
            'is_postponed' => 'boolean',
            'postponement_reason' => 'nullable|string',
        ];
    }
}
<?php

namespace App\Modules\Student\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Handles validation logic for storing a new student.
 * Used in the StudentController@store method.
 */
class AssignSupervisorRequest extends FormRequest
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
            'student_name' => 'required|string|unique:students,student_name',
            'name' => 'required|string',
            'email' => 'required|email',
            'program_id' => 'required|integer|exists:programs,id',
            'current_semester' => 'required|string',
            'department' => 'required|in:SEAT,II,BIHG,CAI,Other',
            'main_supervisor_id' => 'required|integer|exists:lecturers,id',
            'evaluation_type' => 'required|in:FirstEvaluation,ReEvaluation',
            'research_title' => 'nullable|string',
            'is_postponed' => 'boolean',
            'postponement_reason' => 'nullable|string',
        ];
    }
}
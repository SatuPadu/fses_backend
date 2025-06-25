<?php

namespace App\Modules\Student\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

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
            'email' => 'required|email|unique:students,email',
            'program_id' => 'required|integer|exists:programs,id',
            'current_semester' => 'required|string',
            'department' => ['required', Rule::in(['SEAT', 'II', 'BIHG', 'CAI', 'Other'])],
            'country' => 'nullable|string|max:100',
            'main_supervisor_id' => 'required|integer|exists:lecturers,id',
            'evaluation_type' => ['required', Rule::in(['FirstEvaluation', 'ReEvaluation'])],
            'co_supervisor_id' => 'nullable|integer|exists:lecturers,id'
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation errors',
            'data'    => $validator->errors()
        ], 422));
    }
}
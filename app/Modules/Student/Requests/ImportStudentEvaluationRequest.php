<?php

namespace App\Modules\Student\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportStudentEvaluationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:csv,xlsx,xls',
                'max:10240', // 10MB max
            ],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Please select a file to upload.',
            'file.file' => 'The uploaded file is invalid.',
            'file.mimes' => 'The file must be a CSV, XLSX, or XLS file.',
            'file.max' => 'The file size must not exceed 10MB.',
        ];
    }

    /**
     * Get custom attributes for validation rules.
     */
    public function attributes(): array
    {
        return [
            'file' => 'import file',
        ];
    }
} 
<?php

namespace App\Modules\Program\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\Department;
use App\Enums\ProgramName;
use App\Modules\Program\Models\ProgramName as ProgramNameModel;

/**
 * @request UpdateProgramRequest
 * @description Validates input data for updating an academic program.
 */
class UpdateProgramRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Temporarily returns true as no middleware is enforced.
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'program_name' => ['sometimes', 'required', 'string', 'max:255', function ($attribute, $value, $fail) {
                $trimmedValue = trim($value);
                
                // Map full program names to enum values for validation
                $programNameMapping = [
                    'Doctor of Philosophy' => \App\Enums\ProgramName::PHD,
                    'Master of Philosophy' => \App\Enums\ProgramName::MPHIL,
                    'Doctor of Software Engineering' => \App\Enums\ProgramName::DSE
                ];
                
                $enumProgramName = $programNameMapping[$trimmedValue] ?? $trimmedValue;
                
                if (!\App\Enums\ProgramName::isValid($enumProgramName)) {
                    $fail('The program name must be one of: Doctor of Philosophy, Master of Philosophy, Doctor of Software Engineering, or the short forms: ' . implode(', ', \App\Enums\ProgramName::all()));
                }
            }],
            'program_code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('programs', 'program_code')->ignore($this->route('id')),
            ],
            'department' => ['sometimes', 'required', 'string', Rule::in(Department::all())],
            'total_semesters' => 'sometimes|required|integer|min:1',
            'evaluation_semester' => 'sometimes|required|integer|min:1',
        ];
    }
}
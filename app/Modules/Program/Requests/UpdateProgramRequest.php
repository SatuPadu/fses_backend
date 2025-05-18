<?php

namespace App\Modules\Program\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\Department;

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
            'program_name' => 'sometimes|required|string|max:255',
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
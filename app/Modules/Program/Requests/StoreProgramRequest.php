<?php

namespace App\Modules\Program\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\Department;

/**
 * @request StoreProgramRequest
 * @description Validates input data for creating a new academic program.
 */
class StoreProgramRequest extends FormRequest
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
            'program_name' => 'required|string|max:255',
            'program_code' => 'required|string|max:50|unique:programs,program_code',
            'department' => ['required', 'string', Rule::in(Department::all())],
            'total_semesters' => 'required|integer|min:1',
            'evaluation_semester' => 'required|integer|min:1',
        ];
    }
}

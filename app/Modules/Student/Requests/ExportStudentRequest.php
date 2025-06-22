<?php

namespace App\Modules\Student\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'columns' => ['required', 'array', 'min:1'],
            'columns.*' => ['required', 'string', 'in:' . implode(',', $this->getAvailableColumns())],
            'format' => ['required', 'string', Rule::in(['excel', 'xlsx', 'csv', 'pptx'])],
            'filters' => ['sometimes', 'array'],
            'filters.program_id' => ['sometimes', 'integer', 'exists:programs,id'],
            'filters.status' => ['sometimes', 'string', Rule::in(['PD Pertama', 'Re-PD'])],
            'filters.semester' => ['sometimes', 'string'],
            'filters.academic_year' => ['sometimes', 'string'],
            'filters.supervisor_id' => ['sometimes', 'integer', 'exists:lecturers,id'],
            'filters.coordinator_id' => ['sometimes', 'integer', 'exists:lecturers,id'],
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
            'columns.required' => 'At least one column must be selected for export.',
            'columns.array' => 'Columns must be an array.',
            'columns.min' => 'At least one column must be selected.',
            'columns.*.in' => 'Invalid column selected.',
            'format.in' => 'Invalid export format. Supported formats: excel, xlsx, csv, pptx.',
            'filters.program_id.exists' => 'Selected program does not exist.',
            'filters.status.in' => 'Invalid status filter.',
            'filters.supervisor_id.exists' => 'Selected supervisor does not exist.',
            'filters.coordinator_id.exists' => 'Selected coordinator does not exist.',
        ];
    }

    /**
     * Get available columns for export
     *
     * @return array
     */
    private function getAvailableColumns(): array
    {
        return [
            'bil',
            'nama',
            'no_matrik',
            'status_re_pd',
            'pd',
            'kod_program',
            'nama_program',
            'penyelia',
            'penyelia_bersama_2',
            'sem',
            'tajuk_sebelum',
            'pemeriksa_1',
            'pemeriksa_2',
            'pemeriksa_3',
            'pengerusi',
            'country'
        ];
    }
} 
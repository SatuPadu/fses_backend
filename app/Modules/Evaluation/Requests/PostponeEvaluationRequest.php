<?php

namespace App\Modules\Evaluation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PostponeEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Adjust if postponing needs role-based access
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:1000'], // optional justification for postponement
        ];
    }
}
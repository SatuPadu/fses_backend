<?php

namespace App\Modules\Evaluation\Requests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PostponeNominationRequest
{
    public static function validate(Request $request, $id)
    {
        $data = $request->all();
        $data['evaluation_id'] = $id;

        $validator = Validator::make($data, [
            'evaluation_id' => ['required', 'exists:student_evaluations,id'],
            'reason' => ['required', 'string', 'max:1000'],
            'postponed_to' => ['required', 'date', 'after:today'],
        ]);

        if($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}
<?php

namespace App\Modules\Student\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    const DEPARTMENTS = ['SEAT', 'II', 'BIHG', 'CAI', 'Other'];
    const EVALUATION_TYPES = ['FirstEvaluation', 'ReEvaluation'];

    protected $fillable = [
        'student_name',
        'name',
        'email',
        'program_id',
        'current_semester',
        'department',
        'main_supervisor_id',
        'evaluation_type',
        'research_title',
        'is_postponed',
        'postponement_reason',
    ];

    protected $casts = [
        'is_postponed' => 'boolean',
    ];
}
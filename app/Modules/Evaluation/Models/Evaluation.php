<?php

namespace App\Modules\Evaluation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Evaluation extends Model
{
    use HasFactory;

    protected $table = 'student_evaluations';

    protected $fillable = [
        'student_id',
        'nomination_status',
        'examiner1_id',
        'examiner2_id',
        'examiner3_id',
        'chairperson_id',
        'is_auto_assigned',
        'nominated_by',
        'nominated_at',
        'locked_by',
        'locked_at',
        'semester',
        'academic_year',
        'postponed_at',
    ];

    protected $casts = [
        'is_auto_assigned' => 'boolean',
        'nominated_at' => 'datetime',
        'locked_at' => 'datetime',
        'postponed_at' => 'datetime',
    ];
}
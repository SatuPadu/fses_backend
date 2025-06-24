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
        'postponed_to',
        'postponement_reason',
        'is_postponed',
    ];

    protected $casts = [
        'is_auto_assigned' => 'boolean',
        'nominated_at' => 'datetime',
        'locked_at' => 'datetime',
        'postponed_to' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(\App\Modules\Student\Models\Student::class);
    }

    public function examiner1()
    {
        return $this->belongsTo(\App\Modules\UserManagement\Models\Lecturer::class, 'examiner1_id');
    }

    public function examiner2()
    {
        return $this->belongsTo(\App\Modules\UserManagement\Models\Lecturer::class, 'examiner2_id');
    }

    public function examiner3()
    {
        return $this->belongsTo(\App\Modules\UserManagement\Models\Lecturer::class, 'examiner3_id');
    }

    public function chairperson()
    {
        return $this->belongsTo(\App\Modules\UserManagement\Models\Lecturer::class, 'chairperson_id');
    }
}
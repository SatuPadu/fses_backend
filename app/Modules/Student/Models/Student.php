<?php

namespace App\Modules\Student\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    const DEPARTMENTS = ['SEAT', 'II', 'BIHG', 'CAI', 'Other'];
    const EVALUATION_TYPES = ['FirstEvaluation', 'ReEvaluation'];

    protected $fillable = [
        'matric_number',
        'name',
        'email',
        'program_id',
        'current_semester',
        'department',
        'country',
        'main_supervisor_id',
        'evaluation_type',
        'research_title',
        'is_postponed',
        'postponement_reason',
    ];

    protected $casts = [
        'is_postponed' => 'boolean',
    ];

    /**
     * Get the program that the student belongs to
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Program\Models\Program::class);
    }

    /**
     * Get the evaluations for the student
     */
    public function evaluations(): HasMany
    {
        return $this->hasMany(\App\Modules\Evaluation\Models\Evaluation::class);
    }

    /**
     * Get the main supervisor for the student
     */
    public function mainSupervisor(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\UserManagement\Models\Lecturer::class, 'main_supervisor_id');
    }

    /**
     * Get the co-supervisors for the student
     */
    public function coSupervisors(): HasMany
    {
        return $this->hasMany(\App\Modules\Evaluation\Models\CoSupervisor::class);
    }
} 
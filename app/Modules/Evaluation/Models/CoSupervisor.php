<?php

namespace App\Modules\Evaluation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CoSupervisor extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'student_id',
        'lecturer_id',
        'external_name',
        'external_institution',
    ];

    /**
     * Get the student that the co-supervisor belongs to
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Student\Models\Student::class);
    }

    /**
     * Get the lecturer that the co-supervisor belongs to
     */
    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\UserManagement\Models\Lecturer::class);
    }
} 
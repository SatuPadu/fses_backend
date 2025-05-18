<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lecturer extends Model
{
    protected $table = 'lecturers';

    protected $fillable = [
        'name',
        'title',
        'is_from_fai',
        'department',
        'external_institution',
        'specialization',
        'email',
        'phone',
    ];

    public function students(): HasMany
    {
        return $this->hasMany(\App\Modules\Student\Models\Student::class, 'main_supervisor_id');
    }
}
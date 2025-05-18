<?php

namespace App\Modules\Program\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @model Program
 * @description Represents an academic program offered by the institution.
 */
class Program extends Model
{
    protected $table = 'programs';

    protected $fillable = [
        'program_name',
        'program_code',
        'department',
        'total_semesters',
        'evaluation_semester',
    ];
}
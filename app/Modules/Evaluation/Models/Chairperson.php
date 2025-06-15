<?php

namespace App\Modules\Evaluation\Models;

use Illuminate\Database\Eloquent\Model;

class Chairperson extends Model
{
    /**
     * The Examiner Model is associated with the Lecturers table
     * 
     * @var string
     */
    protected $table = 'lecturers';
}

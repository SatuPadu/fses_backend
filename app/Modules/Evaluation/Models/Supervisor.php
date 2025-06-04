<?php

namespace App\Modules\Evaluation\Models;

use Illuminate\Database\Eloquent\Model;

class Supervisor extends Model
{
    /**
     * The Supervisor Model is associated with the Lecturers table
     * 
     * @var string
     */
    protected $table = 'lecturers';
}

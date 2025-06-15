<?php

namespace App\Modules\Evaluation\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    public function supervisor()
    {
        return $this->belongsTo(Supervisor::class, 'main_supervisor_id');
    }
}

<?php

namespace App\Modules\UserManagement\Models;

use App\Modules\Auth\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lecturer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'title',
        'is_from_fai',
        'department', 
        'external_institution',
        'specialization',
        'email',
        'phone',
        'user_id',
        'staff_number',
    ];

    public function user(): HasOne 
    {
        return $this->hasOne(User::class, 'staff_number', 'staff_number');
    }

    /**
     * Get evaluations where this lecturer is the chairperson.
     */
    public function chairpersonEvaluations()
    {
        return $this->hasMany(\App\Modules\Evaluation\Models\Evaluation::class, 'chairperson_id');
    }

    /**
     * Get evaluations where this lecturer is the chairperson (alias for chairpersonEvaluations).
     */
    public function chairedEvaluations()
    {
        return $this->hasMany(\App\Modules\Evaluation\Models\Evaluation::class, 'chairperson_id');
    }

    /**
     * Get evaluations where this lecturer is an examiner (examiner1, examiner2, or examiner3).
     */
    public function examinerEvaluations()
    {
        return $this->hasMany(\App\Modules\Evaluation\Models\Evaluation::class, 'examiner1_id')
            ->orWhere('examiner2_id', $this->id)
            ->orWhere('examiner3_id', $this->id);
    }
}
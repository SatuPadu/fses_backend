<?php

namespace App\Modules\Program\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    /**
     * Get the students enrolled in this program
     */
    public function students(): HasMany
    {
        return $this->hasMany(\App\Modules\Student\Models\Student::class);
    }
}

class ProgramName
{
    public const PHD = 'PhD';
    public const MPHIL = 'MPhil';
    public const DSE = 'DSE';

    public static function all(): array
    {
        return [self::PHD, self::MPHIL, self::DSE];
    }
}
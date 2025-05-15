<?php

namespace App\Modules\Auth\Models;

use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\UserManagement\Models\Lecturer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'staff_number',
        'name',
        'email',
        'password',
        'department',
        'lecturer_id',
        'last_login',
        'password_reset_token',
        'password_reset_expiry',
        'is_active',
        'is_password_updated',
    ];

    protected $hidden = [
        'password',
        'password_reset_token',
    ];

    protected $casts = [
        'last_login' => 'datetime',
        'password_reset_expiry' => 'datetime',
        'is_active' => 'boolean',
        'is_password_updated' => 'boolean',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    // Make sure the password field works with JWT Auth
    public function getAuthPassword()
    {
        return $this->password;
    }

    // Uncomment and update relationships as needed
    public function lecturer()
    {
        return $this->belongsTo(Lecturer::class, 'lecturer_id', 'id');
    }

    // public function roles()
    // {
    //     return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id')
    //                 ->withTimestamps();
    // }

    // public function nominatedEvaluations()
    // {
    //     return $this->hasMany(StudentEvaluation::class, 'nominated_by', 'id');
    // }

    // public function lockedEvaluations()
    // {
    //     return $this->hasMany(StudentEvaluation::class, 'locked_by', 'id');
    // }

    // public function logs()
    // {
    //     return $this->hasMany(Log::class, 'user_id', 'id');
    // }
}
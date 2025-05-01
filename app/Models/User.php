<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

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
     * Get the identifier that will be stored in the JWT token.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key-value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
<?php

namespace App\Modules\Auth\Models;

use Laravel\Sanctum\HasApiTokens;
use App\Modules\Articles\Models\Topic;
use Illuminate\Notifications\Notifiable;
use App\Modules\Articles\Models\UserPreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];


    public function topics()
    {
        return $this->hasManyThrough(
            Topic::class,
            UserPreference::class,
            'user_id',
            'id',
            'id',
            'topic_id'
        );
    }
}
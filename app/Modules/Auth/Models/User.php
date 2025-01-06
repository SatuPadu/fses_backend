<?php

namespace App\Modules\Auth\Models;

use Laravel\Sanctum\HasApiTokens;
use App\Modules\Articles\Models\UserPreference;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    /**
     * Get all user preferences.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function preferences()
    {
        return $this->hasMany(UserPreference::class, 'user_id');
    }

    /**
     * Get user topic preferences.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function topicPreferences()
    {
        return $this->preferences()->ofType('topics');
    }

    /**
     * Get user source preferences.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function sourcePreferences()
    {
        return $this->preferences()->ofType('sources');
    }

    /**
     * Get user author preferences.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function authorPreferences()
    {
        return $this->preferences()->ofType('authors');
    }

    /**
     * Get user topic preferences as an array.
     *
     * @return array
     */
    public function getTopicPreferencesArray(): array
    {
        return $this->topicPreferences()->pluck('value')->toArray();
    }

    /**
     * Get user source preferences as an array.
     *
     * @return array
     */
    public function getSourcePreferencesArray(): array
    {
        return $this->sourcePreferences()->pluck('value')->toArray();
    }

    /**
     * Get user author preferences as an array.
     *
     * @return array
     */
    public function getAuthorPreferencesArray(): array
    {
        return $this->authorPreferences()->pluck('value')->toArray();
    }
}
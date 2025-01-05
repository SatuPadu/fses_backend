<?php

namespace App\Modules\Articles\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

/**
 * Class Article
 *
 * @package App\Modules\Articles\Models
 */
class Article extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'articles';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'description',
        'content',
        'author',
        'source_name',
        'url',
        'thumbnail',
        'published_at',
        'topic',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'published_at' => 'datetime',
    ];

    /**
     * Accessor for the published_at attribute.
     *
     * @param  mixed  $value
     * @return Carbon
     */
    public function getPublishedAtAttribute($value): Carbon
    {
        return Carbon::parse($value);
    }
}
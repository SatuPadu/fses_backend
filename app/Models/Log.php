<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\Auth\Models\User;

class Log extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'logs';

    protected $fillable = [
        'user_id',
        'username',
        'session_id',
        'ip_address',
        'user_agent',
        'action_type',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'additional_details',
        'status',
        'error_message',
        'performed_at',
        'request_url',
        'request_method',
        'referrer_url',
        'duration',
        'system_event',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'additional_details' => 'array',
        'performed_at' => 'datetime',
        'system_event' => 'boolean',
        'duration' => 'integer',
    ];

    /**
     * Get the user that performed the action
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to filter by action type
     */
    public function scopeByActionType($query, $actionType)
    {
        return $query->where('action_type', $actionType);
    }

    /**
     * Scope to filter by entity type
     */
    public function scopeByEntityType($query, $entityType)
    {
        return $query->where('entity_type', $entityType);
    }

    /**
     * Scope to filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('performed_at', [$startDate, $endDate]);
    }
} 
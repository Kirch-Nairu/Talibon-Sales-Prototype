<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'department_id',
        'event_key',
        'source_domain',
        'source_type',
        'source_id',
        'priority',
        'title',
        'message',
        'action_url',
        'requires_acknowledgement',
        'read_at',
        'acknowledged_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'requires_acknowledgement' => 'boolean',
            'read_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}

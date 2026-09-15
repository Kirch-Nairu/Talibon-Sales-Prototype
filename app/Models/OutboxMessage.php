<?php

namespace App\Models;

use App\Domain\Outbox\OutboxMessageStatus;
use Illuminate\Database\Eloquent\Model;

class OutboxMessage extends Model
{
    protected $fillable = [
        'public_id',
        'event_type',
        'aggregate_type',
        'aggregate_id',
        'payload',
        'occurred_at',
        'status',
        'available_at',
        'claimed_at',
        'claimed_by',
        'attempt_count',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'occurred_at' => 'datetime',
            'status' => OutboxMessageStatus::class,
            'available_at' => 'datetime',
            'claimed_at' => 'datetime',
            'attempt_count' => 'integer',
        ];
    }
}

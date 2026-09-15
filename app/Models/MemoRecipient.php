<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemoRecipient extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'memorandum_id', 'user_id', 'delivered_at', 'viewed_at', 'acknowledged_at',
    ];

    protected function casts(): array
    {
        return [
            'delivered_at' => 'datetime',
            'viewed_at' => 'datetime',
            'acknowledged_at' => 'datetime',
        ];
    }

    public function memorandum(): BelongsTo
    {
        return $this->belongsTo(Memorandum::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

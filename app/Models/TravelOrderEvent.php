<?php

namespace App\Models;

use App\Domain\TravelOrders\TravelOrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TravelOrderEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'travel_order_id',
        'actor_user_id',
        'event',
        'from_status',
        'to_status',
        'remarks',
        'occurred_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'from_status' => TravelOrderStatus::class,
            'to_status' => TravelOrderStatus::class,
            'occurred_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function travelOrder(): BelongsTo
    {
        return $this->belongsTo(TravelOrder::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}

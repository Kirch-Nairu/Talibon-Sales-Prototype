<?php

namespace App\Models;

use App\Domain\TravelOrders\TravelOrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TravelOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id',
        'reference_number',
        'issuance_date',
        'purpose',
        'destination',
        'department_id',
        'travel_start_date',
        'travel_end_date',
        'status',
        'recorded_by_user_id',
        'status_updated_by_user_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $travelOrder): void {
            $travelOrder->public_id ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'issuance_date' => 'date',
            'travel_start_date' => 'date',
            'travel_end_date' => 'date',
            'status' => TravelOrderStatus::class,
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function issuedTo(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'travel_order_employee')
            ->withTimestamps();
    }

    public function events(): HasMany
    {
        return $this->hasMany(TravelOrderEvent::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function scopeOrderedRegistry(Builder $query): Builder
    {
        return $query->orderByDesc('issuance_date')->orderByDesc('id');
    }
}

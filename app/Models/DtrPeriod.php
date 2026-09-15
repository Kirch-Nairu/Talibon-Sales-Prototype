<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DtrPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'label',
        'period_start',
        'period_end',
        'status',
        'generated_at',
        'generated_by_user_id',
        'locked_at',
        'locked_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'generated_at' => 'datetime',
            'locked_at' => 'datetime',
        ];
    }

    public function summaries(): HasMany { return $this->hasMany(DtrDailySummary::class); }
    public function generator(): BelongsTo { return $this->belongsTo(User::class, 'generated_by_user_id'); }
    public function locker(): BelongsTo { return $this->belongsTo(User::class, 'locked_by_user_id'); }
    public function payrollPeriod(): HasOne { return $this->hasOne(PayrollPeriod::class); }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'dtr_period_id',
        'label',
        'period_start',
        'period_end',
        'status',
        'calculation_mode',
        'source_notes',
        'processed_at',
        'approved_at',
        'released_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'processed_at' => 'datetime',
            'approved_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }

    public function entries(): HasMany { return $this->hasMany(PayrollEntry::class); }
    public function dtrPeriod(): BelongsTo { return $this->belongsTo(DtrPeriod::class); }
}

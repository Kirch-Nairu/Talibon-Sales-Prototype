<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DtrDailySummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'dtr_period_id',
        'employee_id',
        'work_date',
        'first_in_at',
        'last_out_at',
        'raw_event_count',
        'leave_status',
        'source_status',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'first_in_at' => 'datetime',
            'last_out_at' => 'datetime',
            'raw_event_count' => 'integer',
            'generated_at' => 'datetime',
        ];
    }

    public function period(): BelongsTo { return $this->belongsTo(DtrPeriod::class, 'dtr_period_id'); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
}

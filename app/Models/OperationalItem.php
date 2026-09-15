<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationalItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_type',
        'reference_no',
        'title',
        'department_id',
        'responsible_employee_id',
        'status',
        'priority',
        'target_date',
        'progress_percent',
        'allocated_amount',
        'utilized_amount',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'target_date' => 'date',
            'progress_percent' => 'integer',
            'allocated_amount' => 'decimal:2',
            'utilized_amount' => 'decimal:2',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function responsibleEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'responsible_employee_id');
    }
}

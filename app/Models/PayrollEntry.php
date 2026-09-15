<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_period_id', 'employee_id',
        'dtr_days_with_logs', 'dtr_complete_days', 'dtr_partial_days', 'approved_leave_units', 'dtr_snapshot_status',
        'basic_pay', 'allowances', 'gross_pay', 'gsis', 'philhealth', 'pagibig', 'withholding_tax', 'other_deductions',
        'total_deductions', 'net_pay', 'status',
    ];

    protected function casts(): array
    {
        return [
            'dtr_days_with_logs' => 'integer',
            'dtr_complete_days' => 'integer',
            'dtr_partial_days' => 'integer',
            'approved_leave_units' => 'decimal:3',
            'basic_pay' => 'decimal:2',
            'allowances' => 'decimal:2',
            'gross_pay' => 'decimal:2',
            'gsis' => 'decimal:2',
            'philhealth' => 'decimal:2',
            'pagibig' => 'decimal:2',
            'withholding_tax' => 'decimal:2',
            'other_deductions' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'net_pay' => 'decimal:2',
        ];
    }

    public function payrollPeriod(): BelongsTo { return $this->belongsTo(PayrollPeriod::class); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
}

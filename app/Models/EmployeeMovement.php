<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'movement_type',
        'effective_date',
        'from_department_id',
        'to_department_id',
        'from_position_title',
        'to_position_title',
        'previous_supervisor_employee_id',
        'new_supervisor_employee_id',
        'reason',
        'status',
        'initiated_by_user_id',
        'applied_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'applied_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function fromDepartment(): BelongsTo { return $this->belongsTo(Department::class, 'from_department_id'); }
    public function toDepartment(): BelongsTo { return $this->belongsTo(Department::class, 'to_department_id'); }
    public function previousSupervisor(): BelongsTo { return $this->belongsTo(Employee::class, 'previous_supervisor_employee_id'); }
    public function newSupervisor(): BelongsTo { return $this->belongsTo(Employee::class, 'new_supervisor_employee_id'); }
    public function initiator(): BelongsTo { return $this->belongsTo(User::class, 'initiated_by_user_id'); }
    public function tasks(): HasMany { return $this->hasMany(EmployeeMovementTask::class); }
}

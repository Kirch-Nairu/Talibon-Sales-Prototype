<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OnboardingCase extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'status',
        'appointment_reference',
        'target_department_id',
        'target_position_title',
        'supervisor_employee_id',
        'planned_start_date',
        'started_by_user_id',
        'started_at',
        'completed_by_user_id',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'planned_start_date' => 'date',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function targetDepartment(): BelongsTo { return $this->belongsTo(Department::class, 'target_department_id'); }
    public function supervisor(): BelongsTo { return $this->belongsTo(Employee::class, 'supervisor_employee_id'); }
    public function starter(): BelongsTo { return $this->belongsTo(User::class, 'started_by_user_id'); }
    public function completer(): BelongsTo { return $this->belongsTo(User::class, 'completed_by_user_id'); }
    public function tasks(): HasMany { return $this->hasMany(OnboardingTask::class); }
}

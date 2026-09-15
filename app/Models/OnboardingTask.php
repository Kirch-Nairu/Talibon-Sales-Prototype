<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'onboarding_case_id',
        'task_key',
        'title',
        'category',
        'owner_department_id',
        'is_required',
        'status',
        'due_at',
        'completed_by_user_id',
        'completed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function onboardingCase(): BelongsTo { return $this->belongsTo(OnboardingCase::class); }
    public function ownerDepartment(): BelongsTo { return $this->belongsTo(Department::class, 'owner_department_id'); }
    public function completer(): BelongsTo { return $this->belongsTo(User::class, 'completed_by_user_id'); }
}

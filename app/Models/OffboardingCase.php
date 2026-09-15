<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OffboardingCase extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'separation_type', 'effective_date', 'status', 'reason',
        'initiated_by_user_id', 'initiated_at', 'completed_by_user_id', 'completed_at',
        'account_deactivated_at', 'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'initiated_at' => 'datetime',
            'completed_at' => 'datetime',
            'account_deactivated_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function tasks(): HasMany { return $this->hasMany(OffboardingTask::class); }
    public function initiator(): BelongsTo { return $this->belongsTo(User::class, 'initiated_by_user_id'); }
    public function completer(): BelongsTo { return $this->belongsTo(User::class, 'completed_by_user_id'); }
}

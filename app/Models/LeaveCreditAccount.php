<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveCreditAccount extends Model
{
    protected $fillable = ['employee_id', 'leave_type_id', 'balance'];

    protected function casts(): array
    {
        return ['balance' => 'decimal:3'];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function leaveType(): BelongsTo { return $this->belongsTo(LeaveType::class); }
    public function transactions(): HasMany { return $this->hasMany(LeaveCreditTransaction::class); }
}

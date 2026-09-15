<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeHealthAccessGrant extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'employee_id',
        'can_view',
        'can_manage',
        'purpose',
        'granted_by_user_id',
        'granted_at',
        'expires_at',
        'revoked_at',
        'revoked_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'can_view' => 'boolean',
            'can_manage' => 'boolean',
            'granted_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function granter(): BelongsTo { return $this->belongsTo(User::class, 'granted_by_user_id'); }
    public function revoker(): BelongsTo { return $this->belongsTo(User::class, 'revoked_by_user_id'); }
}

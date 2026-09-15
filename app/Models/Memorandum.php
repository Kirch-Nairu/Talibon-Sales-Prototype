<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Memorandum extends Model
{
    use HasFactory;

    protected $fillable = [
        'memo_number', 'title', 'body', 'issued_by_user_id', 'issued_by_department_id',
        'audience_type', 'requires_acknowledgement', 'classification', 'status',
        'published_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'requires_acknowledgement' => 'boolean',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_user_id');
    }

    public function issuingDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'issued_by_department_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(MemoRecipient::class);
    }
}

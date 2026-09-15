<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetInventorySession extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_code', 'title', 'department_id', 'status', 'inventory_date', 'notes',
        'started_by_user_id', 'closed_by_user_id', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'inventory_date' => 'date',
            'closed_at' => 'datetime',
        ];
    }

    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function scans(): HasMany { return $this->hasMany(AssetInventoryScan::class); }
    public function starter(): BelongsTo { return $this->belongsTo(User::class, 'started_by_user_id'); }
    public function closer(): BelongsTo { return $this->belongsTo(User::class, 'closed_by_user_id'); }
}

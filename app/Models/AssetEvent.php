<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetEvent extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'asset_id',
        'actor_user_id',
        'event_type',
        'from_department_id',
        'to_department_id',
        'from_employee_id',
        'to_employee_id',
        'remarks',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'actor_user_id'); }
    public function fromDepartment(): BelongsTo { return $this->belongsTo(Department::class, 'from_department_id'); }
    public function toDepartment(): BelongsTo { return $this->belongsTo(Department::class, 'to_department_id'); }
    public function fromEmployee(): BelongsTo { return $this->belongsTo(Employee::class, 'from_employee_id'); }
    public function toEmployee(): BelongsTo { return $this->belongsTo(Employee::class, 'to_employee_id'); }
}

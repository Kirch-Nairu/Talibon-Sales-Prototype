<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetMaintenanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id', 'maintenance_type', 'status', 'issue_description', 'service_provider',
        'estimated_cost', 'actual_cost', 'started_on', 'completed_on', 'condition_before',
        'condition_after', 'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'estimated_cost' => 'decimal:2',
            'actual_cost' => 'decimal:2',
            'started_on' => 'date',
            'completed_on' => 'date',
        ];
    }

    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by_user_id'); }
}

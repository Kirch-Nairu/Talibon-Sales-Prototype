<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetDisposal extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id', 'status', 'method', 'authority_reference', 'reason', 'proceeds',
        'recommended_by_user_id', 'decided_by_user_id', 'recommended_at', 'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'proceeds' => 'decimal:2',
            'recommended_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
    public function recommender(): BelongsTo { return $this->belongsTo(User::class, 'recommended_by_user_id'); }
    public function decider(): BelongsTo { return $this->belongsTo(User::class, 'decided_by_user_id'); }
}

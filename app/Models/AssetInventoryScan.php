<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetInventoryScan extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_inventory_session_id', 'asset_id', 'scan_value', 'observed_location',
        'observed_condition', 'verification_status', 'remarks', 'scanned_by_user_id', 'scanned_at',
    ];

    protected function casts(): array
    {
        return ['scanned_at' => 'datetime'];
    }

    public function session(): BelongsTo { return $this->belongsTo(AssetInventorySession::class, 'asset_inventory_session_id'); }
    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
    public function scanner(): BelongsTo { return $this->belongsTo(User::class, 'scanned_by_user_id'); }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetReconciliation extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id', 'status', 'accounting_reference', 'book_value', 'notes',
        'reconciled_by_user_id', 'reconciled_at',
    ];

    protected function casts(): array
    {
        return [
            'book_value' => 'decimal:2',
            'reconciled_at' => 'datetime',
        ];
    }

    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
    public function reconciler(): BelongsTo { return $this->belongsTo(User::class, 'reconciled_by_user_id'); }
}

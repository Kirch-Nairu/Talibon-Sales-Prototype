<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveCreditTransaction extends Model
{
    public $timestamps = false;

    protected $fillable = ['leave_credit_account_id', 'amount', 'entry_type', 'source_type', 'source_id', 'notes', 'actor_user_id', 'created_at'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:3', 'created_at' => 'datetime'];
    }

    public function account(): BelongsTo { return $this->belongsTo(LeaveCreditAccount::class, 'leave_credit_account_id'); }
    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'actor_user_id'); }
}

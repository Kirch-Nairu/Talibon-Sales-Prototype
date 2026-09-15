<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    protected $fillable = ['code', 'name', 'tracks_balance', 'entitlement_label', 'is_active'];

    protected function casts(): array
    {
        return ['tracks_balance' => 'boolean', 'is_active' => 'boolean'];
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(LeaveCreditAccount::class);
    }
}

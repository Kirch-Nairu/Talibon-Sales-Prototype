<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IntegrationClient extends Model
{
    protected $fillable = [
        'public_id',
        'name',
        'description',
        'is_active',
        'requests_per_minute',
        'contact_name',
        'contact_email',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'requests_per_minute' => 'integer',
        ];
    }

    public function credentials(): HasMany
    {
        return $this->hasMany(IntegrationClientCredential::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationProofWrite extends Model
{
    protected $fillable = [
        'public_id',
        'integration_client_id',
        'integration_client_credential_id',
        'operation',
        'value',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(IntegrationClient::class, 'integration_client_id');
    }

    public function credential(): BelongsTo
    {
        return $this->belongsTo(IntegrationClientCredential::class, 'integration_client_credential_id');
    }
}

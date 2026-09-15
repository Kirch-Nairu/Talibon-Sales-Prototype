<?php

namespace App\Models;

use App\Domain\Integration\IntegrationIdempotencyStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationIdempotencyRecord extends Model
{
    protected $fillable = [
        'integration_client_id',
        'integration_client_credential_id',
        'operation',
        'idempotency_key_hash',
        'request_fingerprint',
        'status',
        'processing_token',
        'execution_attempts',
        'response_status',
        'response_body',
        'started_at',
        'completed_at',
        'failed_at',
    ];

    protected $hidden = [
        'idempotency_key_hash',
        'request_fingerprint',
        'processing_token',
    ];

    protected function casts(): array
    {
        return [
            'status' => IntegrationIdempotencyStatus::class,
            'execution_attempts' => 'integer',
            'response_status' => 'integer',
            'response_body' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(IntegrationClient::class, 'integration_client_id');
    }

    public function credential(): BelongsTo
    {
        return $this->belongsTo(IntegrationClientCredential::class, 'integration_client_credential_id');
    }
}

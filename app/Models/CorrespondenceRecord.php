<?php

namespace App\Models;

use App\Domain\Correspondence\CorrespondenceClassification;
use App\Domain\Correspondence\CorrespondenceLifecycleState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CorrespondenceRecord extends Model
{
    protected $fillable = [
        'public_id',
        'external_reference_no',
        'source',
        'channel',
        'sender_name',
        'sender_organization',
        'sender_contact',
        'subject',
        'summary',
        'received_at',
        'received_source_identity',
        'receiving_integration_client_id',
        'receiving_integration_client_credential_id',
        'receiving_department_id',
        'registered_by_user_id',
        'registered_at',
        'municipal_reference_no',
        'classification',
        'classified_at',
        'classified_by_user_id',
        'routed_by_user_id',
        'routed_at',
        'action_started_by_user_id',
        'action_started_at',
        'originating_external_reference',
        'lifecycle_state',
        'workflow_transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'sender_contact' => 'array',
            'received_at' => 'datetime',
            'registered_at' => 'datetime',
            'classified_at' => 'datetime',
            'routed_at' => 'datetime',
            'action_started_at' => 'datetime',
            'classification' => CorrespondenceClassification::class,
            'lifecycle_state' => CorrespondenceLifecycleState::class,
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function events(): HasMany
    {
        return $this->hasMany(CorrespondenceEvent::class);
    }

    public function receivingIntegrationClient(): BelongsTo
    {
        return $this->belongsTo(IntegrationClient::class, 'receiving_integration_client_id');
    }

    public function receivingIntegrationCredential(): BelongsTo
    {
        return $this->belongsTo(IntegrationClientCredential::class, 'receiving_integration_client_credential_id');
    }

    public function receivingDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'receiving_department_id');
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by_user_id');
    }

    public function classifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'classified_by_user_id');
    }

    public function routedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'routed_by_user_id');
    }

    public function actionStartedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'action_started_by_user_id');
    }

    public function workflowTransaction(): BelongsTo
    {
        return $this->belongsTo(WorkflowTransaction::class, 'workflow_transaction_id');
    }
}

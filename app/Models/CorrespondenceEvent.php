<?php

namespace App\Models;

use App\Domain\Correspondence\CorrespondenceLifecycleState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class CorrespondenceEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'correspondence_record_id',
        'event',
        'previous_lifecycle_state',
        'new_lifecycle_state',
        'actor_user_id',
        'integration_client_actor_id',
        'office_department_id',
        'remarks',
        'metadata',
        'correlation_id',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'previous_lifecycle_state' => CorrespondenceLifecycleState::class,
            'new_lifecycle_state' => CorrespondenceLifecycleState::class,
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(static function (): never {
            throw new LogicException('Correspondence history is append-only and cannot be updated.');
        });

        static::deleting(static function (): never {
            throw new LogicException('Correspondence history is append-only and cannot be deleted.');
        });
    }

    public function correspondence(): BelongsTo
    {
        return $this->belongsTo(CorrespondenceRecord::class, 'correspondence_record_id');
    }

    public function actorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function integrationClientActor(): BelongsTo
    {
        return $this->belongsTo(IntegrationClient::class, 'integration_client_actor_id');
    }

    public function officeDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'office_department_id');
    }
}

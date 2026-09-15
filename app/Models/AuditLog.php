<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'actor_user_id',
        'integration_client_id',
        'actor_department_id',
        'action',
        'entity_type',
        'entity_id',
        'outcome',
        'summary',
        'ip_address',
        'user_agent',
        'correlation_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function integrationClient(): BelongsTo
    {
        return $this->belongsTo(IntegrationClient::class, 'integration_client_id');
    }

    public function actorDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'actor_department_id');
    }
}

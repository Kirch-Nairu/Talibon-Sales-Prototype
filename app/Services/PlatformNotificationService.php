<?php

namespace App\Services;

use App\Models\Department;
use App\Models\PlatformNotification;
use App\Models\User;

class PlatformNotificationService
{
    public function notifyUser(User $recipient, array $payload): PlatformNotification
    {
        return PlatformNotification::query()->firstOrCreate(
            [
                'user_id' => $recipient->id,
                'event_key' => $payload['event_key'],
            ],
            [
                'department_id' => $payload['department_id'] ?? $recipient->employee?->department_id,
                'source_domain' => $payload['source_domain'],
                'source_type' => $payload['source_type'] ?? null,
                'source_id' => $payload['source_id'] ?? null,
                'priority' => $payload['priority'] ?? 'info',
                'title' => $payload['title'],
                'message' => $payload['message'],
                'action_url' => $payload['action_url'] ?? null,
                'requires_acknowledgement' => $payload['requires_acknowledgement'] ?? false,
                'expires_at' => $payload['expires_at'] ?? null,
            ],
        );
    }

    public function notifyDepartment(Department $department, array $payload): int
    {
        if (! $department->is_active || ! $department->is_routable) {
            return 0;
        }

        $recipients = User::query()
            ->where('is_active', true)
            ->whereHas('employee', fn ($query) => $query
                ->where('department_id', $department->id)
                ->where('employment_status', 'active'))
            ->with('employee:id,user_id,department_id')
            ->get();

        foreach ($recipients as $recipient) {
            $this->notifyUser($recipient, $payload + ['department_id' => $department->id]);
        }

        return $recipients->count();
    }
}

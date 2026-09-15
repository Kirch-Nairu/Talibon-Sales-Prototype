<?php

namespace App\Services;

use App\Models\MemoRecipient;
use App\Models\PlatformNotification;
use App\Models\TransactionEvent;
use App\Models\User;

final class NotificationFeedQuery
{
    /** @return array<string, mixed> */
    public static function emptyFeed(): array
    {
        return [
            'pendingMemo' => null,
            'unreadMemoCount' => 0,
            'unreadPlatformNotificationCount' => 0,
            'notifications' => [],
            'notificationCount' => 0,
        ];
    }

    /** @return array<string, mixed> */
    public function feed(User $user): array
    {
        $user->loadMissing('employee.department');

        $recipientQuery = MemoRecipient::query()
            ->where('user_id', $user->id)
            ->whereHas('memorandum', function ($query): void {
                $query->where('status', 'published')
                    ->whereNotNull('published_at')
                    ->where(function ($expiry): void {
                        $expiry->whereNull('expires_at')->orWhere('expires_at', '>', now());
                    });
            });

        $unreadMemoCount = (clone $recipientQuery)->whereNull('viewed_at')->count();
        $pendingMemo = $this->pendingMemo($recipientQuery);
        $memoNotifications = $this->memoNotifications($recipientQuery);

        $platformBase = PlatformNotification::query()
            ->where('user_id', $user->id)
            ->where(function ($expiry): void {
                $expiry->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });

        $hasPlatformHistory = (clone $platformBase)->exists();
        $unreadPlatformNotificationCount = (clone $platformBase)
            ->whereNull('read_at')
            ->count();
        $platformNotifications = $this->platformNotifications($platformBase);
        $workflowNotifications = $hasPlatformHistory
            ? []
            : $this->workflowFallbackNotifications($user);

        $notifications = collect([
            ...$memoNotifications,
            ...$platformNotifications,
            ...$workflowNotifications,
        ])
            ->sortByDesc('created_at')
            ->take(10)
            ->values()
            ->all();

        return [
            'pendingMemo' => $pendingMemo,
            'unreadMemoCount' => $unreadMemoCount,
            'unreadPlatformNotificationCount' => $unreadPlatformNotificationCount,
            'notifications' => $notifications,
            'notificationCount' => count($notifications),
        ];
    }

    private function pendingMemo($recipientQuery): ?array
    {
        $recipient = (clone $recipientQuery)
            ->whereNull('viewed_at')
            ->with([
                'memorandum.issuer:id,name',
                'memorandum.issuingDepartment:id,name,short_name',
            ])
            ->latest('delivered_at')
            ->first();

        if (! $recipient) {
            return null;
        }

        return [
            'id' => $recipient->memorandum->id,
            'memo_number' => $recipient->memorandum->memo_number,
            'title' => $recipient->memorandum->title,
            'issuer' => $recipient->memorandum->issuer?->name,
            'department' => $recipient->memorandum->issuingDepartment?->short_name
                ?? $recipient->memorandum->issuingDepartment?->name,
            'requires_acknowledgement' => $recipient->memorandum->requires_acknowledgement,
        ];
    }

    private function memoNotifications($recipientQuery): array
    {
        return (clone $recipientQuery)
            ->whereNull('viewed_at')
            ->with('memorandum:id,memo_number,title,requires_acknowledgement')
            ->latest('delivered_at')
            ->limit(4)
            ->get()
            ->map(fn (MemoRecipient $recipient): array => [
                'key' => 'memo-'.$recipient->id,
                'type' => 'memorandum',
                'title' => 'New memorandum',
                'message' => $recipient->memorandum->memo_number.' · '.$recipient->memorandum->title,
                'url' => '/memoranda/'.$recipient->memorandum->id,
                'created_at' => $recipient->delivered_at?->toIso8601String(),
                'urgent' => (bool) $recipient->memorandum->requires_acknowledgement,
                'requires_acknowledgement' => (bool) $recipient->memorandum->requires_acknowledgement,
            ])
            ->all();
    }

    private function platformNotifications($platformBase): array
    {
        return (clone $platformBase)
            ->whereNull('read_at')
            ->latest('created_at')
            ->limit(6)
            ->get()
            ->map(fn (PlatformNotification $notification): array => [
                'id' => $notification->id,
                'key' => 'platform-'.$notification->id,
                'type' => $notification->source_domain,
                'title' => $notification->title,
                'message' => $notification->message,
                'url' => $notification->action_url ?? '/dashboard',
                'read_url' => '/notifications/'.$notification->id.'/read',
                'acknowledgement_url' => $notification->requires_acknowledgement
                    ? '/notifications/'.$notification->id.'/acknowledge'
                    : null,
                'created_at' => $notification->created_at?->toIso8601String(),
                'urgent' => in_array($notification->priority, [
                    'urgent',
                    'critical',
                    'action_required',
                    'acknowledgement_required',
                ], true),
                'requires_acknowledgement' => $notification->requires_acknowledgement,
            ])
            ->all();
    }

    private function workflowFallbackNotifications(User $user): array
    {
        $departmentId = $user->employee?->department_id;
        if (! $departmentId) {
            return [];
        }

        return TransactionEvent::query()
            ->with([
                'transaction:id,reference_no,title,priority,status,current_department_id',
                'fromDepartment:id,name,short_name',
            ])
            ->where('to_department_id', $departmentId)
            ->whereColumn('from_department_id', '<>', 'to_department_id')
            ->whereIn('action', [
                'submitted',
                'forward',
                'send_to_mayor',
                'return_origin',
                'request_information',
            ])
            ->where('created_at', '>=', now()->subDay())
            ->whereHas('transaction', function ($query) use ($departmentId): void {
                $query->where('current_department_id', $departmentId)
                    ->whereNotIn('status', ['approved', 'disapproved', 'closed']);
            })
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(function (TransactionEvent $event) use ($user): array {
                $transaction = $event->transaction;
                $origin = $event->fromDepartment?->short_name
                    ?? $event->fromDepartment?->name
                    ?? 'another office';

                return [
                    'key' => 'tx-event-'.$event->id,
                    'type' => 'transaction',
                    'title' => $user->employee?->department?->code === 'MAYOR'
                        ? 'New executive request received'
                        : 'New transaction received',
                    'message' => $transaction->reference_no.' · '.$transaction->title.' · from '.$origin,
                    'url' => '/transactions/'.$transaction->id,
                    'created_at' => $event->created_at?->toIso8601String(),
                    'urgent' => in_array($transaction->priority, ['high', 'urgent'], true),
                ];
            })
            ->all();
    }
}

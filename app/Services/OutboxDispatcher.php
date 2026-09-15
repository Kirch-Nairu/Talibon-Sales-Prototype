<?php

namespace App\Services;

use App\Domain\Outbox\OutboxMessageStatus;
use App\Models\OutboxMessage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

class OutboxDispatcher
{
    /**
     * Claim pending messages for a future transport. This class intentionally does not publish externally.
     *
     * @return Collection<int, OutboxMessage>
     */
    public function claimPending(string $workerId, int $limit = 50): Collection
    {
        $workerId = trim($workerId);
        if ($workerId === '' || $limit < 1 || $limit > 500) {
            throw new InvalidArgumentException('A worker identity and a claim limit between 1 and 500 are required.');
        }

        return DB::transaction(function () use ($workerId, $limit): Collection {
            $messages = OutboxMessage::query()
                ->where('status', OutboxMessageStatus::Pending->value)
                ->where('available_at', '<=', now())
                ->orderBy('id')
                ->limit($limit)
                ->lock('for update skip locked')
                ->get();

            foreach ($messages as $message) {
                $message->forceFill([
                    'status' => OutboxMessageStatus::Claimed,
                    'claimed_at' => now(),
                    'claimed_by' => $workerId,
                    'attempt_count' => $message->attempt_count + 1,
                    'last_error' => null,
                ])->save();
            }

            return $messages;
        });
    }

    public function releaseClaim(OutboxMessage $message, string $workerId, ?string $error = null): OutboxMessage
    {
        return DB::transaction(function () use ($message, $workerId, $error): OutboxMessage {
            $locked = OutboxMessage::query()->lockForUpdate()->findOrFail($message->id);

            if ($locked->status !== OutboxMessageStatus::Claimed || $locked->claimed_by !== $workerId) {
                throw new LogicException('Outbox claim ownership does not match the releasing worker.');
            }

            $locked->forceFill([
                'status' => OutboxMessageStatus::Pending,
                'claimed_at' => null,
                'claimed_by' => null,
                'last_error' => $error,
                'available_at' => now(),
            ])->save();

            return $locked;
        });
    }
}

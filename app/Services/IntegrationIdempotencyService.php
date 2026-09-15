<?php

namespace App\Services;

use App\Domain\Integration\IntegrationClientContext;
use App\Domain\Integration\IntegrationIdempotencyDecision;
use App\Domain\Integration\IntegrationIdempotencyDecisionType;
use App\Domain\Integration\IntegrationIdempotencyStatus;
use App\Models\IntegrationIdempotencyRecord;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

class IntegrationIdempotencyService
{
    public function begin(
        IntegrationClientContext $context,
        string $operation,
        string $idempotencyKey,
        string $requestFingerprint,
    ): IntegrationIdempotencyDecision {
        return DB::transaction(function () use ($context, $operation, $idempotencyKey, $requestFingerprint): IntegrationIdempotencyDecision {
            $keyHash = hash('sha256', $idempotencyKey);
            $processingToken = (string) Str::uuid();
            $now = now();

            $inserted = DB::table('integration_idempotency_records')->insertOrIgnore([
                'integration_client_id' => $context->client->id,
                'integration_client_credential_id' => $context->credential->id,
                'operation' => $operation,
                'idempotency_key_hash' => $keyHash,
                'request_fingerprint' => $requestFingerprint,
                'status' => IntegrationIdempotencyStatus::Processing->value,
                'processing_token' => $processingToken,
                'execution_attempts' => 1,
                'started_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if ($inserted === 1) {
                $record = IntegrationIdempotencyRecord::query()
                    ->where('processing_token', $processingToken)
                    ->firstOrFail();

                return new IntegrationIdempotencyDecision(
                    IntegrationIdempotencyDecisionType::Execute,
                    $record,
                    $processingToken,
                );
            }

            $record = $this->scopedQuery($context, $operation, $keyHash)
                ->lockForUpdate()
                ->firstOrFail();

            if (! hash_equals($record->request_fingerprint, $requestFingerprint)) {
                return new IntegrationIdempotencyDecision(
                    IntegrationIdempotencyDecisionType::Conflict,
                    $record,
                );
            }

            if ($record->status === IntegrationIdempotencyStatus::Completed) {
                return new IntegrationIdempotencyDecision(
                    IntegrationIdempotencyDecisionType::Replay,
                    $record,
                );
            }

            if ($record->status === IntegrationIdempotencyStatus::Processing) {
                return new IntegrationIdempotencyDecision(
                    IntegrationIdempotencyDecisionType::InProgress,
                    $record,
                );
            }

            return $this->restartFailed($record, $processingToken, $now);
        });
    }

    /**
     * @param  array<string, mixed>  $responseBody
     */
    public function complete(
        IntegrationIdempotencyRecord $record,
        string $processingToken,
        int $responseStatus,
        array $responseBody,
    ): void {
        $locked = IntegrationIdempotencyRecord::query()
            ->lockForUpdate()
            ->findOrFail($record->id);

        $this->assertProcessingOwner($locked, $processingToken);
        $locked->forceFill([
            'status' => IntegrationIdempotencyStatus::Completed,
            'response_status' => $responseStatus,
            'response_body' => $responseBody,
            'processing_token' => null,
            'completed_at' => now(),
            'failed_at' => null,
        ])->save();
    }

    public function markFailed(IntegrationIdempotencyRecord $record, string $processingToken): void
    {
        DB::transaction(function () use ($record, $processingToken): void {
            $locked = IntegrationIdempotencyRecord::query()
                ->lockForUpdate()
                ->find($record->id);

            if ($locked === null
                || $locked->status !== IntegrationIdempotencyStatus::Processing
                || ! is_string($locked->processing_token)
                || ! hash_equals($locked->processing_token, $processingToken)) {
                return;
            }

            $locked->forceFill([
                'status' => IntegrationIdempotencyStatus::Failed,
                'processing_token' => null,
                'response_status' => null,
                'response_body' => null,
                'completed_at' => null,
                'failed_at' => now(),
            ])->save();
        });
    }

    private function scopedQuery(IntegrationClientContext $context, string $operation, string $keyHash): Builder
    {
        return IntegrationIdempotencyRecord::query()
            ->where('integration_client_id', $context->client->id)
            ->where('integration_client_credential_id', $context->credential->id)
            ->where('operation', $operation)
            ->where('idempotency_key_hash', $keyHash);
    }

    private function restartFailed(
        IntegrationIdempotencyRecord $record,
        string $processingToken,
        CarbonInterface $startedAt,
    ): IntegrationIdempotencyDecision {
        $record->forceFill([
            'status' => IntegrationIdempotencyStatus::Processing,
            'processing_token' => $processingToken,
            'execution_attempts' => $record->execution_attempts + 1,
            'response_status' => null,
            'response_body' => null,
            'started_at' => $startedAt,
            'completed_at' => null,
            'failed_at' => null,
        ])->save();

        return new IntegrationIdempotencyDecision(
            IntegrationIdempotencyDecisionType::Execute,
            $record,
            $processingToken,
        );
    }

    private function assertProcessingOwner(IntegrationIdempotencyRecord $record, string $processingToken): void
    {
        if ($record->status !== IntegrationIdempotencyStatus::Processing
            || ! is_string($record->processing_token)
            || ! hash_equals($record->processing_token, $processingToken)) {
            throw new LogicException('Idempotency processing ownership was lost before completion.');
        }
    }
}

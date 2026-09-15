<?php

namespace App\Http\Middleware;

use App\Domain\Integration\IntegrationClientContext;
use App\Domain\Integration\IntegrationErrorCode;
use App\Domain\Integration\IntegrationIdempotencyDecisionType;
use App\Domain\Integration\IntegrationIdempotencyExecutionRejected;
use App\Domain\Integration\IntegrationRequestAttributes;
use App\Models\IntegrationIdempotencyRecord;
use App\Services\AuditLogger;
use App\Services\IntegrationErrorResponse;
use App\Services\IntegrationIdempotencyResponse;
use App\Services\IntegrationIdempotencyService;
use App\Services\IntegrationRequestFingerprint;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ExecuteIdempotentIntegrationRequest
{
    public function __construct(
        private readonly IntegrationIdempotencyService $idempotency,
        private readonly IntegrationRequestFingerprint $fingerprints,
        private readonly IntegrationIdempotencyResponse $snapshots,
        private readonly AuditLogger $audit,
        private readonly IntegrationErrorResponse $errors,
    ) {
    }

    public function handle(Request $request, Closure $next, string $operation): Response
    {
        $context = $request->attributes->get(IntegrationRequestAttributes::CLIENT_CONTEXT);
        if (! $context instanceof IntegrationClientContext) {
            return $this->errors->make($request, IntegrationErrorCode::AuthenticationFailed, 401);
        }

        $key = $this->idempotencyKey($request);
        if ($key === null) {
            $this->audit->recordIntegration(
                $context->client,
                'integration.idempotency.key_rejected',
                'Idempotent integration operation rejected because the Idempotency-Key header was missing or invalid.',
                'denied',
            );

            return $this->errors->make($request, IntegrationErrorCode::IdempotencyKeyRequired, 422);
        }

        $decision = $this->idempotency->begin(
            $context,
            $operation,
            $key,
            $this->fingerprints->hash($request),
        );

        if ($decision->type === IntegrationIdempotencyDecisionType::Replay) {
            $this->audit->recordIntegration(
                $context->client,
                'integration.idempotency.replayed',
                'Completed idempotent integration result replayed without re-executing the application action.',
            );

            return $this->snapshots->replay($request, $decision->record);
        }

        if ($decision->type === IntegrationIdempotencyDecisionType::Conflict) {
            $this->audit->recordIntegration(
                $context->client,
                'integration.idempotency.conflict',
                'Idempotency key reuse rejected because the request fingerprint differs.',
                'denied',
            );

            return $this->errors->make($request, IntegrationErrorCode::IdempotencyConflict, 409);
        }

        if ($decision->type === IntegrationIdempotencyDecisionType::InProgress) {
            $this->audit->recordIntegration(
                $context->client,
                'integration.idempotency.in_progress',
                'Concurrent duplicate idempotent integration attempt rejected while the original execution is still processing.',
                'denied',
            );

            return $this->errors->make(
                $request,
                IntegrationErrorCode::IdempotencyInProgress,
                409,
                ['Retry-After' => 1],
            );
        }

        $processingToken = $decision->processingToken;
        if (! is_string($processingToken)) {
            throw new \LogicException('Executable idempotency decision is missing processing ownership.');
        }

        $this->audit->recordIntegration(
            $context->client,
            'integration.idempotency.execution.started',
            'Original idempotent integration execution claimed.',
        );

        try {
            return DB::transaction(function () use ($request, $next, $context, $decision, $processingToken): Response {
                $response = $next($request);
                if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
                    throw new IntegrationIdempotencyExecutionRejected($response);
                }
                if (! $response instanceof JsonResponse) {
                    throw new \LogicException('Idempotent integration routes must return JSON responses.');
                }

                $this->idempotency->complete(
                    $decision->record,
                    $processingToken,
                    $response->getStatusCode(),
                    $this->snapshots->capture($response),
                );
                $this->audit->recordIntegration(
                    $context->client,
                    'integration.idempotency.execution.completed',
                    'Original idempotent integration execution completed and became replayable.',
                );

                return $response;
            });
        } catch (IntegrationIdempotencyExecutionRejected $exception) {
            $this->markFailed($context, $decision->record, $processingToken);

            return $exception->response;
        } catch (Throwable $exception) {
            $this->markFailed($context, $decision->record, $processingToken);
            throw $exception;
        }
    }

    private function idempotencyKey(Request $request): ?string
    {
        $key = $request->header('Idempotency-Key');
        if (! is_string($key)) {
            return null;
        }

        $key = trim($key);

        return strlen($key) >= 8
            && strlen($key) <= 200
            && preg_match('/^[A-Za-z0-9._:-]+$/', $key) === 1
            ? $key
            : null;
    }

    private function markFailed(
        IntegrationClientContext $context,
        IntegrationIdempotencyRecord $record,
        string $processingToken,
    ): void {
        $this->idempotency->markFailed($record, $processingToken);
        $this->audit->recordIntegration(
            $context->client,
            'integration.idempotency.execution.failed',
            'Idempotent integration execution failed before a replayable response was committed.',
            'denied',
        );
    }
}

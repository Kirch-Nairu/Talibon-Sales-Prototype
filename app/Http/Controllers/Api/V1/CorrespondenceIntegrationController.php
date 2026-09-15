<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Integration\IntegrationClientContext;
use App\Domain\Integration\IntegrationErrorCode;
use App\Domain\Integration\IntegrationRequestAttributes;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CorrespondenceReceiveRequest;
use App\Models\CorrespondenceRecord;
use App\Services\AuditLogger;
use App\Services\CorrespondenceAccessDecider;
use App\Services\CorrespondenceReceiveService;
use App\Services\IntegrationErrorResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CorrespondenceIntegrationController extends Controller
{
    public function store(
        CorrespondenceReceiveRequest $request,
        CorrespondenceReceiveService $receive,
        IntegrationErrorResponse $errors,
    ): JsonResponse {
        $context = $request->attributes->get(IntegrationRequestAttributes::CLIENT_CONTEXT);
        if (! $context instanceof IntegrationClientContext) {
            return $errors->make($request, IntegrationErrorCode::AuthenticationFailed, 401);
        }

        $correlationId = (string) $request->attributes->get(IntegrationRequestAttributes::CORRELATION_ID);
        $record = $receive->receive($context, $request->validated(), $correlationId);

        return response()->json([
            'correspondence' => [
                'public_id' => $record->public_id,
                'reference' => $record->external_reference_no,
                'lifecycle_state' => $record->lifecycle_state->value,
                'received_at' => $record->received_at?->toISOString(),
            ],
            'correlation_id' => $correlationId,
        ], 201);
    }

    public function show(
        Request $request,
        string $publicId,
        CorrespondenceAccessDecider $access,
        IntegrationErrorResponse $errors,
        AuditLogger $audit,
    ): JsonResponse {
        $context = $request->attributes->get(IntegrationRequestAttributes::CLIENT_CONTEXT);
        if (! $context instanceof IntegrationClientContext) {
            return $errors->make($request, IntegrationErrorCode::AuthenticationFailed, 401);
        }

        $record = CorrespondenceRecord::query()->where('public_id', $publicId)->first();
        if ($record === null || ! $access->canIntegrationReadStatus($context->client, $record)) {
            $audit->recordIntegration(
                $context->client,
                'correspondence.status.denied',
                'Integration correspondence status lookup did not resolve to a client-owned record.',
                'denied',
                'correspondence_record',
                $record?->id,
            );

            return $errors->make($request, IntegrationErrorCode::CorrespondenceNotFound, 404);
        }

        $audit->recordIntegration(
            $context->client,
            'correspondence.status.read',
            'Integration client read the safe live status of client-owned correspondence.',
            entityType: 'correspondence_record',
            entityId: $record->id,
        );

        $correlationId = (string) $request->attributes->get(IntegrationRequestAttributes::CORRELATION_ID);

        return response()->json([
            'correspondence' => [
                'public_id' => $record->public_id,
                'reference' => $record->external_reference_no,
                'lifecycle_state' => $record->lifecycle_state->value,
                'received_at' => $record->received_at?->toISOString(),
                'registered_at' => $record->registered_at?->toISOString(),
                'classification' => $record->classification?->externalStatusValue(),
            ],
            'correlation_id' => $correlationId,
        ]);
    }
}

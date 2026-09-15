<?php

namespace App\Services;

use App\Domain\Integration\IntegrationRequestAttributes;
use App\Models\IntegrationIdempotencyRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LogicException;

class IntegrationIdempotencyResponse
{
    /**
     * @return array<string, mixed>
     */
    public function capture(JsonResponse $response): array
    {
        $body = $response->getData(true);
        if (! is_array($body)) {
            throw new LogicException('Idempotent integration responses must use a JSON object or array.');
        }

        unset($body['correlation_id']);

        return $body;
    }

    public function replay(Request $request, IntegrationIdempotencyRecord $record): JsonResponse
    {
        if (! is_int($record->response_status) || ! is_array($record->response_body)) {
            throw new LogicException('Completed idempotency record is missing a replayable response snapshot.');
        }

        $body = $record->response_body;
        $body['correlation_id'] = $request->attributes->get(IntegrationRequestAttributes::CORRELATION_ID);

        return response()->json(
            $body,
            $record->response_status,
            ['Idempotency-Replayed' => 'true'],
        );
    }
}

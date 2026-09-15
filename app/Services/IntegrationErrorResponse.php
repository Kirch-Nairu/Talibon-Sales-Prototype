<?php

namespace App\Services;

use App\Domain\Integration\IntegrationErrorCode;
use App\Domain\Integration\IntegrationRequestAttributes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class IntegrationErrorResponse
{
    /**
     * @param  array<string, string|int>  $headers
     */
    public function make(
        Request $request,
        IntegrationErrorCode $code,
        int $status,
        array $headers = [],
    ): JsonResponse {
        $correlationId = $request->attributes->get(IntegrationRequestAttributes::CORRELATION_ID);
        if (! is_string($correlationId) || ! Str::isUuid($correlationId)) {
            $correlationId = (string) Str::uuid();
        }

        return response()->json([
            'error' => [
                'code' => $code->value,
                'message' => $code->message(),
            ],
            'correlation_id' => $correlationId,
        ], $status, $headers);
    }
}

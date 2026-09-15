<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Integration\IntegrationClientContext;
use App\Domain\Integration\IntegrationRequestAttributes;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IntegrationSelfRequest;
use Illuminate\Http\JsonResponse;
use LogicException;

class IntegrationSelfController extends Controller
{
    public function __invoke(IntegrationSelfRequest $request): JsonResponse
    {
        $context = $request->attributes->get(IntegrationRequestAttributes::CLIENT_CONTEXT);
        if (! $context instanceof IntegrationClientContext) {
            throw new LogicException('Integration client context is missing after authentication middleware.');
        }

        return response()->json([
            'client' => [
                'public_id' => $context->client->public_id,
                'name' => $context->client->name,
            ],
            'credential' => [
                'public_id' => $context->credential->public_id,
                'scopes' => $context->scopes,
                'expires_at' => $context->credential->expires_at?->toIso8601String(),
            ],
            'correlation_id' => $request->attributes->get(IntegrationRequestAttributes::CORRELATION_ID),
        ]);
    }
}

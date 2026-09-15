<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Integration\IntegrationClientContext;
use App\Domain\Integration\IntegrationOperation;
use App\Domain\Integration\IntegrationRequestAttributes;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IntegrationProofWriteRequest;
use App\Services\IntegrationProofWriteService;
use Illuminate\Http\JsonResponse;
use LogicException;

class IntegrationProofWriteController extends Controller
{
    public function __invoke(
        IntegrationProofWriteRequest $request,
        IntegrationProofWriteService $proofWrites,
    ): JsonResponse {
        $context = $request->attributes->get(IntegrationRequestAttributes::CLIENT_CONTEXT);
        if (! $context instanceof IntegrationClientContext) {
            throw new LogicException('Integration client context is missing after authentication middleware.');
        }

        $write = $proofWrites->execute(
            $context,
            IntegrationOperation::ProofWrite->value,
            $request->validated('value'),
        );

        return response()->json([
            'proof' => [
                'public_id' => $write->public_id,
                'value' => $write->value,
            ],
            'correlation_id' => $request->attributes->get(IntegrationRequestAttributes::CORRELATION_ID),
        ], 201);
    }
}

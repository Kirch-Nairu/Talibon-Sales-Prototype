<?php

namespace App\Services;

use App\Domain\Integration\IntegrationClientContext;
use App\Models\IntegrationProofWrite;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IntegrationProofWriteService
{
    public function __construct(private readonly TransactionalOutbox $outbox)
    {
    }

    public function execute(
        IntegrationClientContext $context,
        string $operation,
        string $value,
    ): IntegrationProofWrite {
        return DB::transaction(function () use ($context, $operation, $value): IntegrationProofWrite {
            $write = IntegrationProofWrite::query()->create([
                'public_id' => (string) Str::uuid(),
                'integration_client_id' => $context->client->id,
                'integration_client_credential_id' => $context->credential->id,
                'operation' => $operation,
                'value' => $value,
            ]);

            $this->outbox->record(
                'integration.proof_write.created',
                'integration_proof_write',
                $write->public_id,
                [
                    'proof_public_id' => $write->public_id,
                    'client_public_id' => $context->client->public_id,
                    'value' => $value,
                ],
            );

            return $write;
        });
    }
}

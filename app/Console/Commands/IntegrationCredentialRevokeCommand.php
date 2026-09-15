<?php

namespace App\Console\Commands;

use App\Models\IntegrationClientCredential;
use App\Services\IntegrationCredentialService;
use Illuminate\Console\Command;

class IntegrationCredentialRevokeCommand extends Command
{
    protected $signature = 'integration:credential-revoke
        {credential : Credential public UUID}';

    protected $description = 'Explicitly revoke an integration client credential';

    public function handle(IntegrationCredentialService $credentials): int
    {
        $credential = IntegrationClientCredential::query()
            ->where('public_id', (string) $this->argument('credential'))
            ->first();

        if ($credential === null) {
            $this->error('Integration credential not found.');

            return self::FAILURE;
        }

        $credentials->revoke($credential);
        $this->info('Integration credential revoked.');

        return self::SUCCESS;
    }
}

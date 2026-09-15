<?php

namespace App\Console\Commands;

use App\Models\IntegrationClient;
use App\Services\IntegrationCredentialService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Throwable;

class IntegrationCredentialIssueCommand extends Command
{
    protected $signature = 'integration:credential-issue
        {client : Integration client public UUID}
        {--scope=* : Scope to grant; repeat for multiple scopes}
        {--expires-at= : Optional ISO-8601 expiration timestamp}';

    protected $description = 'Issue a one-time-display credential for an integration client';

    public function handle(IntegrationCredentialService $credentials): int
    {
        $client = IntegrationClient::query()
            ->where('public_id', (string) $this->argument('client'))
            ->first();

        if ($client === null) {
            $this->error('Integration client not found.');

            return self::FAILURE;
        }

        try {
            $issued = $credentials->issue(
                $client,
                array_values($this->option('scope')),
                $this->expiration(),
            );
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->warn('Store this credential now. The plaintext secret cannot be retrieved later.');
        $this->line($issued->plainTextToken);

        return self::SUCCESS;
    }

    private function expiration(): ?CarbonImmutable
    {
        $value = trim((string) $this->option('expires-at'));
        if ($value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (Throwable) {
            throw new InvalidArgumentException('The expiration timestamp is invalid.');
        }
    }
}

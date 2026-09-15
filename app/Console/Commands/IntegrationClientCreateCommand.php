<?php

namespace App\Console\Commands;

use App\Services\IntegrationClientService;
use Illuminate\Console\Command;
use InvalidArgumentException;

class IntegrationClientCreateCommand extends Command
{
    protected $signature = 'integration:client-create
        {name : Human-readable integration client name}
        {--description= : Purpose or description}
        {--rpm=60 : Requests per minute}
        {--contact-name= : Optional operational contact}
        {--contact-email= : Optional operational contact email}';

    protected $description = 'Create an integration client without issuing a credential';

    public function handle(IntegrationClientService $clients): int
    {
        try {
            $client = $clients->create(
                (string) $this->argument('name'),
                (int) $this->option('rpm'),
                $this->nullableOption('description'),
                $this->nullableOption('contact-name'),
                $this->nullableOption('contact-email'),
            );
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Integration client created.');
        $this->line('Public ID: '.$client->public_id);
        $this->line('Requests/minute: '.$client->requests_per_minute);

        return self::SUCCESS;
    }

    private function nullableOption(string $name): ?string
    {
        $value = trim((string) $this->option($name));

        return $value === '' ? null : $value;
    }
}

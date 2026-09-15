<?php

namespace App\Services;

use App\Models\IntegrationClient;
use App\Models\User;
use Illuminate\Support\Str;
use InvalidArgumentException;

class IntegrationClientService
{
    public function create(
        string $name,
        int $requestsPerMinute = 60,
        ?string $description = null,
        ?string $contactName = null,
        ?string $contactEmail = null,
        ?User $createdBy = null,
    ): IntegrationClient {
        $this->assertRateLimit($requestsPerMinute);

        return IntegrationClient::query()->create([
            'public_id' => (string) Str::uuid(),
            'name' => trim($name),
            'description' => $description,
            'is_active' => true,
            'requests_per_minute' => $requestsPerMinute,
            'contact_name' => $contactName,
            'contact_email' => $contactEmail,
            'created_by_user_id' => $createdBy?->id,
        ]);
    }

    public function findByPublicId(string $publicId): ?IntegrationClient
    {
        return IntegrationClient::query()
            ->where('public_id', $publicId)
            ->first();
    }

    private function assertRateLimit(int $requestsPerMinute): void
    {
        if ($requestsPerMinute < 1 || $requestsPerMinute > 10000) {
            throw new InvalidArgumentException('Requests per minute must be between 1 and 10000.');
        }
    }
}

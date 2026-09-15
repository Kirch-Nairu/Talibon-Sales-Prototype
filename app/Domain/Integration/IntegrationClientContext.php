<?php

namespace App\Domain\Integration;

use App\Models\IntegrationClient;
use App\Models\IntegrationClientCredential;

final readonly class IntegrationClientContext
{
    /**
     * @param  array<int, string>  $scopes
     */
    public function __construct(
        public IntegrationClient $client,
        public IntegrationClientCredential $credential,
        public array $scopes,
    ) {
    }

    public function hasScope(IntegrationScope $scope): bool
    {
        return in_array($scope->value, $this->scopes, true);
    }
}

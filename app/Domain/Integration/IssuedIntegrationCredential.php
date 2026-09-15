<?php

namespace App\Domain\Integration;

use Carbon\CarbonInterface;

final readonly class IssuedIntegrationCredential
{
    /**
     * @param  array<int, string>  $scopes
     */
    public function __construct(
        public string $publicId,
        public string $plainTextToken,
        public array $scopes,
        public ?CarbonInterface $expiresAt,
    ) {
    }
}

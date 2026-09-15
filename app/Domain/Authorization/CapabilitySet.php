<?php

namespace App\Domain\Authorization;

final readonly class CapabilitySet
{
    /**
     * @param  array<int, string>  $capabilities
     */
    public function __construct(private array $capabilities)
    {
    }

    /**
     * @param  array<int, string>  $capabilities
     */
    public static function from(array $capabilities): self
    {
        return new self(array_values(array_unique($capabilities)));
    }

    public function allows(string $capability): bool
    {
        return in_array($capability, $this->capabilities, true);
    }

    /**
     * @return array<int, string>
     */
    public function all(): array
    {
        return $this->capabilities;
    }
}

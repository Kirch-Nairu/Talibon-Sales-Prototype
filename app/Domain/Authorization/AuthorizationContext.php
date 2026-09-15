<?php

namespace App\Domain\Authorization;

final readonly class AuthorizationContext
{
    /**
     * @param  array<int, string>  $actorRoles
     * @param  array<int, int>  $delegatedOfficeIds
     */
    public function __construct(
        public int $actorUserId,
        public ?int $actorEmployeeId,
        public bool $actorActive,
        public array $actorRoles,
        public CapabilitySet $capabilities,
        public ?int $actorOfficeId,
        public ?string $actorOfficeCode,
        public array $delegatedOfficeIds,
        public ?int $resourceOfficeId,
        public ?string $resourceOfficeCode,
        public ?int $resourceOriginOfficeId,
        public ?int $resourceAssignedEmployeeId,
        public ?string $classification,
        public ?string $workflowState,
        public string $requestedAction,
    ) {
    }

    public function hasRole(string ...$roles): bool
    {
        return array_intersect($roles, $this->actorRoles) !== [];
    }

    public function can(string $capability): bool
    {
        return $this->capabilities->allows($capability);
    }

    public function actorHasEmployee(): bool
    {
        return $this->actorEmployeeId !== null;
    }

    public function isActorResourceOffice(): bool
    {
        return $this->actorOfficeId !== null
            && $this->actorOfficeId === $this->resourceOfficeId;
    }

    public function isActorOriginOffice(): bool
    {
        return $this->actorOfficeId !== null
            && $this->actorOfficeId === $this->resourceOriginOfficeId;
    }

    public function isActorAssigned(): bool
    {
        return $this->actorEmployeeId !== null
            && $this->actorEmployeeId === $this->resourceAssignedEmployeeId;
    }

    public function hasResourceOfficeAuthority(): bool
    {
        if ($this->isActorResourceOffice()) {
            return true;
        }

        return $this->resourceOfficeId !== null
            && in_array($this->resourceOfficeId, $this->delegatedOfficeIds, true);
    }
}

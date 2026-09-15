<?php

namespace App\Domain\Workflow;

use Illuminate\Validation\ValidationException;

final readonly class WorkflowDefinition
{
    /**
     * @param  array<int, string>  $terminalStatuses
     * @param  array<string, WorkflowTransitionRule>  $transitions
     */
    public function __construct(
        private string $initialStatus,
        private array $terminalStatuses,
        private array $transitions,
    ) {
    }

    public static function fromArray(array $definition): self
    {
        $transitions = [];

        foreach ($definition['transitions'] ?? [] as $action => $rule) {
            $transitions[$action] = WorkflowTransitionRule::fromArray($action, $rule);
        }

        return new self(
            initialStatus: (string) ($definition['initial_status'] ?? 'submitted'),
            terminalStatuses: array_values($definition['terminal_statuses'] ?? []),
            transitions: $transitions,
        );
    }

    public function initialStatus(): string
    {
        return $this->initialStatus;
    }

    /**
     * @return array<int, string>
     */
    public function actions(): array
    {
        return array_keys($this->transitions);
    }

    public function isTerminal(string $status): bool
    {
        return in_array($status, $this->terminalStatuses, true);
    }

    public function transition(string $action): WorkflowTransitionRule
    {
        if (! isset($this->transitions[$action])) {
            throw ValidationException::withMessages(['action' => 'Unsupported workflow action.']);
        }

        return $this->transitions[$action];
    }
}

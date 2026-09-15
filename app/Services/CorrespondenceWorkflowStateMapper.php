<?php

namespace App\Services;

use App\Models\WorkflowTransaction;
use App\Domain\Workflow\WorkflowDefinitionResolver;

final class CorrespondenceWorkflowStateMapper
{
    public function __construct(
        private readonly WorkflowDefinitionResolver $definitions,
    ) {
    }

    public function representsRouted(WorkflowTransaction $workflow): bool
    {
        $definition = $this->definitions->resolve($workflow);

        return $workflow->status === $definition->initialStatus()
            && $workflow->assigned_employee_id === null;
    }

    public function permitsInAction(WorkflowTransaction $workflow): bool
    {
        $definition = $this->definitions->resolve($workflow);

        if ($definition->isTerminal($workflow->status)) {
            return false;
        }

        if ($workflow->assigned_employee_id !== null) {
            return true;
        }

        $activeStatuses = [];
        foreach (['mark_review', 'send_to_mayor'] as $action) {
            if (! in_array($action, $definition->actions(), true)) {
                continue;
            }

            $status = $definition->transition($action)->status;
            if ($status !== null) {
                $activeStatuses[] = $status;
            }
        }

        return in_array($workflow->status, array_values(array_unique($activeStatuses)), true);
    }
}

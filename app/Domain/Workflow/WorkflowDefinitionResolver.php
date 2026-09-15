<?php

namespace App\Domain\Workflow;

use App\Models\WorkflowTransaction;

class WorkflowDefinitionResolver
{
    public function resolve(WorkflowTransaction|string|null $subject = null): WorkflowDefinition
    {
        $transactionType = $subject instanceof WorkflowTransaction
            ? $subject->transaction_type
            : $subject;

        $definition = config('workflow.default', []);

        if ($transactionType) {
            $definition = array_replace_recursive(
                $definition,
                config("workflow.types.{$transactionType}", []),
            );
        }

        return WorkflowDefinition::fromArray($definition);
    }
}

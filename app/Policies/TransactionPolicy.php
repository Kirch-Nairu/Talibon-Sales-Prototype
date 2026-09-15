<?php

namespace App\Policies;

use App\Domain\Workflow\Authorization\TransactionAccessDecider;
use App\Domain\Workflow\Authorization\TransactionAuthorizationContextFactory;
use App\Models\User;
use App\Models\WorkflowTransaction;

final readonly class TransactionPolicy
{
    public function __construct(
        private TransactionAuthorizationContextFactory $contexts,
        private TransactionAccessDecider $access,
    ) {
    }

    public function viewAny(User $user): bool
    {
        return $this->allows($user, TransactionAccessDecider::VIEW_ANY);
    }

    public function view(User $user, WorkflowTransaction $transaction): bool
    {
        return $this->allows($user, TransactionAccessDecider::VIEW, $transaction);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, TransactionAccessDecider::CREATE);
    }

    public function transition(User $user, WorkflowTransaction $transaction): bool
    {
        return $this->allows($user, TransactionAccessDecider::TRANSITION, $transaction);
    }

    public function assign(User $user, WorkflowTransaction $transaction): bool
    {
        return $this->allows($user, TransactionAccessDecider::ASSIGN, $transaction);
    }

    public function mayorDecision(User $user, WorkflowTransaction $transaction): bool
    {
        return $this->allows($user, TransactionAccessDecider::MAYOR_DECISION, $transaction);
    }

    private function allows(
        User $user,
        string $action,
        ?WorkflowTransaction $transaction = null,
    ): bool {
        return $this->access->allows(
            $this->contexts->make($user, $action, $transaction),
        );
    }
}

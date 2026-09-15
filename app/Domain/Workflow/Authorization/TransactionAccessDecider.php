<?php

namespace App\Domain\Workflow\Authorization;

use App\Domain\Authorization\AuthorizationContext;

final class TransactionAccessDecider
{
    public const VIEW_ANY = 'view_any';

    public const VIEW = 'view';

    public const CREATE = 'create';

    public const TRANSITION = 'transition';

    public const ASSIGN = 'assign';

    public const MAYOR_DECISION = 'mayor_decision';

    public function allows(AuthorizationContext $context): bool
    {
        return match ($context->requestedAction) {
            self::VIEW_ANY => $this->canEnterTransactionArea($context),
            self::VIEW => $this->canView($context),
            self::CREATE => $this->canEnterTransactionArea($context),
            self::TRANSITION => $this->canTransition($context),
            self::ASSIGN => $this->canAssign($context),
            self::MAYOR_DECISION => $this->canMayorDecision($context),
            default => false,
        };
    }

    private function canEnterTransactionArea(AuthorizationContext $context): bool
    {
        return $context->actorActive && $context->actorHasEmployee();
    }

    private function canView(AuthorizationContext $context): bool
    {
        return $context->can(TransactionCapabilities::VIEW_ALL)
            || $context->isActorOriginOffice()
            || $context->hasResourceOfficeAuthority();
    }

    private function canTransition(AuthorizationContext $context): bool
    {
        if ($context->hasRole('system_admin')) {
            return true;
        }

        return $context->can(TransactionCapabilities::TRANSITION)
            && $context->hasResourceOfficeAuthority();
    }

    private function canAssign(AuthorizationContext $context): bool
    {
        if ($context->hasRole('system_admin')) {
            return true;
        }

        return $context->can(TransactionCapabilities::ASSIGN)
            && $context->hasResourceOfficeAuthority();
    }

    private function canMayorDecision(AuthorizationContext $context): bool
    {
        if ($context->hasRole('system_admin')) {
            return true;
        }

        return $context->can(TransactionCapabilities::MAYOR_DECISION)
            && $context->actorOfficeCode === 'MAYOR'
            && $context->resourceOfficeCode === 'MAYOR';
    }
}

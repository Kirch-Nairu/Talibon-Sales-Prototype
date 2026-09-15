<?php

namespace App\Domain\Workflow\Authorization;

use App\Domain\Authorization\CapabilitySet;

final class TransactionCapabilities
{
    public const VIEW_ALL = 'transaction.view_all';

    public const TRANSITION = 'transaction.transition';

    public const ASSIGN = 'transaction.assign';

    public const MAYOR_DECISION = 'transaction.mayor_decision';

    /**
     * @var array<string, array<int, string>>
     */
    private const ROLE_CAPABILITIES = [
        'system_admin' => [
            self::VIEW_ALL,
            self::TRANSITION,
            self::ASSIGN,
            self::MAYOR_DECISION,
        ],
        'mayor_approver' => [
            self::VIEW_ALL,
            self::MAYOR_DECISION,
        ],
        'mayor_staff' => [
            self::VIEW_ALL,
            self::TRANSITION,
            self::ASSIGN,
        ],
        'department_head' => [
            self::TRANSITION,
            self::ASSIGN,
        ],
        'department_staff' => [
            self::TRANSITION,
        ],
        'hr_officer' => [
            self::TRANSITION,
            self::ASSIGN,
        ],
        'legislative_staff' => [
            self::TRANSITION,
            self::ASSIGN,
        ],
    ];

    /**
     * @param  array<int, string>  $roles
     */
    public function resolve(array $roles): CapabilitySet
    {
        $capabilities = [];

        foreach ($roles as $role) {
            array_push($capabilities, ...(self::ROLE_CAPABILITIES[$role] ?? []));
        }

        return CapabilitySet::from($capabilities);
    }
}

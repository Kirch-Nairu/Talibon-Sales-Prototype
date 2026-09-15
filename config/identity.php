<?php

return [
    'privileged_roles' => [
        'system_admin',
        'mayor_approver',
        'mayor_staff',
        'department_head',
        'department_staff',
        'hr_officer',
        'legislative_staff',
    ],

    'mfa' => [
        'issuer' => env('MFA_ISSUER', 'Municipality of Talibon Intra-Office Portal'),
        'totp_window' => 1,
        'recovery_code_count' => 10,
    ],

    'rate_limits' => [
        'login' => [
            'attempts' => 5,
            'decay_seconds' => 60,
        ],
        'mfa' => [
            'attempts' => 5,
            'decay_seconds' => 60,
        ],
    ],
];

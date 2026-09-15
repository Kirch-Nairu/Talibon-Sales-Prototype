<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Workflow definition
    |--------------------------------------------------------------------------
    |
    | This is the compatibility definition for the existing municipal routing
    | flow. Transaction-type overrides can progressively replace shared rules
    | without scattering office/state/SLA policy through controllers/services.
    |
    */

    'default' => [
        'initial_status' => 'submitted',

        'terminal_statuses' => [
            'approved',
            'disapproved',
            'closed',
        ],

        'transitions' => [
            'assign' => [
                'destination' => 'current',
                'requires_assignment' => true,
            ],
            'mark_review' => [
                'status' => 'for_review',
                'destination' => 'current',
            ],
            'forward' => [
                'status' => 'submitted',
                'destination' => 'target',
                'clear_assignment' => true,
                'refresh_received_at' => true,
            ],
            'send_to_mayor' => [
                'status' => 'for_approval',
                'destination' => 'office',
                'office_code' => 'MAYOR',
                'clear_assignment' => true,
                'refresh_received_at' => true,
            ],
            'return_origin' => [
                'status' => 'returned',
                'destination' => 'origin',
                'clear_assignment' => true,
                'refresh_received_at' => true,
            ],
            'request_information' => [
                'status' => 'information_requested',
                'destination' => 'origin',
                'clear_assignment' => true,
                'refresh_received_at' => true,
            ],
            'approve' => [
                'status' => 'approved',
                'destination' => 'current',
                'completes' => true,
            ],
            'disapprove' => [
                'status' => 'disapproved',
                'destination' => 'current',
                'completes' => true,
            ],
        ],
    ],

    'types' => [
        // Add transaction-type-specific transition overrides here.
    ],

    'sla' => [
        'priority_days' => [
            'normal' => 5,
            'high' => 3,
            'urgent' => 1,
        ],
    ],

    'executive_attention_office_codes' => [
        'MAYOR',
    ],
];

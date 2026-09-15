<?php

return [
    'demo_password' => env('PROTOTYPE_DEMO_PASSWORD'),
    'minimum_demo_password_length' => 16,
    'blocked_demo_password_sha256' => [
        // Historical shared prototype fallback. Keep only its digest so the obsolete value cannot become active again.
        'd686cf65a7881710f094721693c95cc33748f94b197055c4861aa6a218ddf21c',
    ],
];

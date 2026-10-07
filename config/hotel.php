<?php

return [
    'property' => [
        'name' => env('HOTEL_NAME', 'Urbanview Daniela Jambi by RedDoorz'),
        'short_name' => env('HOTEL_SHORT_NAME', 'Daniela Jambi'),
    ],

    'reservation' => [
        'default_security_deposit' => (int) env('HOTEL_DEFAULT_SECURITY_DEPOSIT', 100000),
    ],

    'seed' => [
        'admin_name' => env('SEED_ADMIN_NAME', 'System Administrator'),
        'admin_email' => env('SEED_ADMIN_EMAIL', 'admin@daniela.test'),
        'admin_password' => env('SEED_ADMIN_PASSWORD', 'ChangeMe123!'),
    ],
];

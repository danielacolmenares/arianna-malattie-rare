<?php

use App\Enums\UserRole;

return [
    'password' => env('DEMO_USER_PASSWORD', env('DEMO_ADMIN_PASSWORD')),
    'users' => [
        [
            'name' => 'Amministratrice demo',
            'email' => env('DEMO_ADMIN_EMAIL'),
            'role' => UserRole::Administrator,
        ],
        [
            'name' => 'Redattore demo',
            'email' => env('DEMO_EDITOR_EMAIL'),
            'role' => UserRole::Editor,
        ],
        [
            'name' => 'Validatore demo',
            'email' => env('DEMO_VALIDATOR_EMAIL'),
            'role' => UserRole::Validator,
        ],
        [
            'name' => 'Direttore UOC demo',
            'email' => env('DEMO_DIRECTOR_EMAIL'),
            'role' => UserRole::Director,
        ],
    ],
];

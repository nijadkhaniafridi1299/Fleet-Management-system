
<?php

return [
    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver' => 'jwt',
            'provider' => 'users',
        ],
    
        'oms' => [
            'driver' => 'jwt',
            'provider' => 'customers',
        ]
    ],
    'SYMBOLS_DATA' => '/[^\p{L}\p{N}\s\-_.@,$]/u',

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => \App\Model\User::class
        ],
        'customers' => [
            'driver' => 'eloquent',
            'model' => \App\Model\Customer::class
        ]
    ]
];

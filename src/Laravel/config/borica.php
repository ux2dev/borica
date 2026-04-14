<?php

declare(strict_types=1);

return [
    'default' => env('BORICA_MERCHANT', 'default'),

    'merchants' => [
        'default' => [
            'terminal' => env('BORICA_TERMINAL'),
            'merchant_id' => env('BORICA_MERCHANT_ID'),
            'merchant_name' => env('BORICA_MERCHANT_NAME'),
            'environment' => env('BORICA_ENVIRONMENT', 'development'),
            'currency' => env('BORICA_CURRENCY', 'EUR'),
            'country' => env('BORICA_COUNTRY', 'BG'),
            'timezone_offset' => env('BORICA_TIMEZONE_OFFSET', '+03'),
            'private_key' => env('BORICA_PRIVATE_KEY', storage_path('borica/private.key')),
            'private_key_passphrase' => env('BORICA_PRIVATE_KEY_PASSPHRASE'),
            'borica_public_key' => env('BORICA_PUBLIC_KEY'),
        ],
    ],

    'routes' => [
        'enabled' => true,
        'prefix' => 'borica',
        'middleware' => ['web'],
    ],

    'redirect' => [
        'success' => '/payment/success',
        'failure' => '/payment/failure',
    ],
];

<?php

declare(strict_types=1);

return [
    'default' => env('BORICA_TENANT', 'default'),

    'tenants' => [
        'default' => [
            'environment' => env('BORICA_ENVIRONMENT', 'production'),

            'cgi' => [
                'terminal' => env('BORICA_TERMINAL'),
                'merchant_id' => env('BORICA_MERCHANT_ID'),
                'merchant_name' => env('BORICA_MERCHANT_NAME'),
                'currency' => env('BORICA_CURRENCY', 'EUR'),
                'country' => env('BORICA_COUNTRY', 'BG'),
                'timezone_offset' => env('BORICA_TIMEZONE_OFFSET', '+03'),
                'private_key' => env('BORICA_PRIVATE_KEY', storage_path('borica/private.key')),
                'private_key_passphrase' => env('BORICA_PRIVATE_KEY_PASSPHRASE'),
                'certificate' => env('BORICA_CERTIFICATE'),
                'borica_public_key' => env('BORICA_PUBLIC_KEY'),
            ],

            'checkout' => [
                'base_url' => env('BORICA_CHECKOUT_BASE_URL', 'https://uat-api-checkout.infopay.bg'),
                'auth_id' => env('BORICA_CHECKOUT_AUTH_ID'),
                'auth_secret' => env('BORICA_CHECKOUT_AUTH_SECRET'),
                'shop_id' => env('BORICA_CHECKOUT_SHOP_ID'),
                'private_key' => env('BORICA_CHECKOUT_PRIVATE_KEY', storage_path('borica/checkout.key')),
                'private_key_passphrase' => env('BORICA_CHECKOUT_PRIVATE_KEY_PASSPHRASE'),
                'certificate' => env('BORICA_CHECKOUT_CERTIFICATE'),
            ],

            'erp' => [
                'base_url' => env('BORICA_ERP_BASE_URL'),
                'unique_id' => env('BORICA_ERP_UNIQUE_ID'),
                'access_token' => env('BORICA_ERP_ACCESS_TOKEN'),
            ],
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

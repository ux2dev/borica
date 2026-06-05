<?php

declare(strict_types=1);

test('publishes a tenants-based config with a default tenant', function () {
    $config = require __DIR__ . '/../../src/Laravel/config/borica.php';

    expect($config)->toHaveKeys(['default', 'tenants', 'routes', 'redirect']);
    expect($config['tenants'])->toHaveKey('default');
    expect($config['tenants']['default'])->toHaveKeys(['cgi', 'checkout', 'erp']);
})->group('laravel');

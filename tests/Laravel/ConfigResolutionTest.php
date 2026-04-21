<?php

declare(strict_types=1);

test('config has default merchant key', function () {
    expect(config('borica.cgi.default'))->toBe('default');
});

test('config has merchants array', function () {
    expect(config('borica.cgi.merchants.default'))->toBeArray();
    expect(config('borica.cgi.merchants.default.terminal'))->toBe('V1800001');
});

test('config has routes section', function () {
    expect(config('borica.routes.enabled'))->toBeTrue();
    expect(config('borica.routes.prefix'))->toBe('borica');
    expect(config('borica.routes.middleware'))->toBe(['web']);
});

test('config has redirect section', function () {
    expect(config('borica.redirect.success'))->toBe('/payment/success');
    expect(config('borica.redirect.failure'))->toBe('/payment/failure');
});

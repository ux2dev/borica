<?php

declare(strict_types=1);

test('config has a default tenant key', function () {
    expect(config('borica.default'))->toBe('default');
});

test('config has a tenants array with the default tenant cgi config', function () {
    expect(config('borica.tenants.default'))->toBeArray();
    expect(config('borica.tenants.default.cgi.terminal'))->toBe('V1800001');
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

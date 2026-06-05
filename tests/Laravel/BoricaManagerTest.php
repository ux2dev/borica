<?php

declare(strict_types=1);

use Ux2Dev\Borica\Borica;
use Ux2Dev\Borica\Cgi\CgiArea;
use Ux2Dev\Borica\Cgi\Request\PaymentRequest;
use Ux2Dev\Borica\Exception\ConfigurationException;
use Ux2Dev\Borica\Laravel\BoricaManager;

test('client() resolves the default tenant to a Borica instance', function () {
    expect(app(BoricaManager::class)->client())->toBeInstanceOf(Borica::class);
});

test('client() is cached per manager instance', function () {
    $manager = app(BoricaManager::class);
    expect($manager->client())->toBe($manager->client());
});

test('tenant() returns an immutable clone with the target tenant active', function () {
    $manager = app(BoricaManager::class);
    $other = $manager->tenant('other');

    expect($other)->not->toBe($manager);
    expect($manager->currentTenant())->toBe('default');
    expect($other->currentTenant())->toBe('other');
});

test('cgi() is proxied to the active tenant Borica', function () {
    expect(app(BoricaManager::class)->cgi())->toBeInstanceOf(CgiArea::class);
});

test('cgi()->getGatewayUrl() reflects the tenant environment', function () {
    expect(app(BoricaManager::class)->cgi()->getGatewayUrl())
        ->toBe('https://3dsgate-dev.borica.bg/cgi-bin/cgi_link');
});

test('cgi()->payments()->purchase() builds a signed PaymentRequest', function () {
    $request = app(BoricaManager::class)->cgi()->payments()->purchase(new \Ux2Dev\Borica\Cgi\Request\Input\PaymentInput(
        amount: '10.50',
        order: '000001',
        description: 'Test payment',
        mInfo: ['cardholderName' => 'John Doe', 'email' => 'john@example.com'],
    ));

    expect($request)->toBeInstanceOf(PaymentRequest::class);
    expect($request->toArray()['AMOUNT'])->toBe('10.50');
});

test('an unknown tenant throws ConfigurationException', function () {
    app(BoricaManager::class)->tenant('nonexistent')->client();
})->throws(ConfigurationException::class, 'Borica tenant "nonexistent" is not configured');

test('resolves a CGI private key from a file path', function () {
    config()->set('borica.tenants.file-based.cgi', [
        'terminal' => 'V1800001',
        'merchant_id' => 'MERCHANT01',
        'merchant_name' => 'File Shop',
        'private_key' => __DIR__ . '/../fixtures/test_private_key.pem',
        'environment' => 'development',
        'currency' => 'EUR',
    ]);

    expect(app(BoricaManager::class)->tenant('file-based')->cgi())->toBeInstanceOf(CgiArea::class);
});

test('resolves a CGI private key from a raw PEM string', function () {
    config()->set('borica.tenants.pem-based.cgi', [
        'terminal' => 'V1800001',
        'merchant_id' => 'MERCHANT01',
        'merchant_name' => 'PEM Shop',
        'private_key' => file_get_contents(__DIR__ . '/../fixtures/test_private_key.pem'),
        'environment' => 'development',
        'currency' => 'EUR',
    ]);

    expect(app(BoricaManager::class)->tenant('pem-based')->cgi())->toBeInstanceOf(CgiArea::class);
});

test('tenantByTerminal maps a configured terminal to its tenant name', function () {
    expect(app(BoricaManager::class)->tenantByTerminal('V1800001'))->toBe('default');
});

test('tenantByTerminal returns null for an unknown terminal', function () {
    expect(app(BoricaManager::class)->tenantByTerminal('UNKNOWN1'))->toBeNull();
});

test('resolveTerminalUsing resolves a tenant from a custom callback', function () {
    $manager = app(BoricaManager::class);

    $manager->resolveTerminalUsing(fn (string $terminal): ?array => $terminal === 'DBTERMN1' ? [
        'name' => 'tenant-a',
        'cgi' => [
            'terminal' => 'DBTERMN1',
            'merchant_id' => 'DBMERCH001',
            'merchant_name' => 'DB Tenant A',
            'private_key' => file_get_contents(__DIR__ . '/../fixtures/test_private_key.pem'),
            'environment' => 'development',
            'currency' => 'EUR',
        ],
    ] : null);

    $name = $manager->tenantByTerminal('DBTERMN1');
    expect($name)->toBe('tenant-a');
    expect($manager->tenant($name)->cgi())->toBeInstanceOf(CgiArea::class);
});

test('resolveTerminalUsing defaults the tenant name to the terminal', function () {
    $manager = app(BoricaManager::class);
    $manager->resolveTerminalUsing(fn (string $t): ?array => $t === 'DBTERMN1' ? [
        'cgi' => [
            'terminal' => 'DBTERMN1',
            'merchant_id' => 'DBMERCH001',
            'merchant_name' => 'DB Tenant A',
            'private_key' => file_get_contents(__DIR__ . '/../fixtures/test_private_key.pem'),
        ],
    ] : null);

    expect($manager->tenantByTerminal('DBTERMN1'))->toBe('DBTERMN1');
});

test('static tenant config takes precedence over the terminal resolver', function () {
    $manager = app(BoricaManager::class);
    $called = false;
    $manager->resolveTerminalUsing(function () use (&$called) {
        $called = true;
        return null;
    });

    expect($manager->tenantByTerminal('V1800001'))->toBe('default');
    expect($called)->toBeFalse();
});

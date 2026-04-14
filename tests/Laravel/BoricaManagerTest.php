<?php

declare(strict_types=1);

use Ux2Dev\Borica\Borica;
use Ux2Dev\Borica\Laravel\BoricaManager;
use Ux2Dev\Borica\Request\PaymentRequest;

test('resolves default merchant', function () {
    $manager = app(BoricaManager::class);

    $borica = $manager->merchant('default');

    expect($borica)->toBeInstanceOf(Borica::class);
});

test('caches resolved merchants by name', function () {
    $manager = app(BoricaManager::class);

    $first = $manager->merchant('default');
    $second = $manager->merchant('default');

    expect($first)->toBe($second);
});

test('resolves merchant from runtime array config', function () {
    $manager = app(BoricaManager::class);

    $borica = $manager->merchant([
        'terminal' => 'V1800001',
        'merchant_id' => 'MERCHANT01',
        'merchant_name' => 'Runtime Shop',
        'private_key' => file_get_contents(__DIR__ . '/../fixtures/test_private_key.pem'),
        'private_key_passphrase' => null,
        'borica_public_key' => null,
        'environment' => 'development',
        'currency' => 'EUR',
        'country' => 'BG',
        'timezone_offset' => '+03',
    ]);

    expect($borica)->toBeInstanceOf(Borica::class);
});

test('runtime array merchants are not cached', function () {
    $manager = app(BoricaManager::class);

    $config = [
        'terminal' => 'V1800001',
        'merchant_id' => 'MERCHANT01',
        'merchant_name' => 'Runtime Shop',
        'private_key' => file_get_contents(__DIR__ . '/../fixtures/test_private_key.pem'),
        'private_key_passphrase' => null,
        'borica_public_key' => null,
        'environment' => 'development',
        'currency' => 'EUR',
        'country' => 'BG',
        'timezone_offset' => '+03',
    ];

    $first = $manager->merchant($config);
    $second = $manager->merchant($config);

    expect($first)->not->toBe($second);
});

test('proxies methods to default merchant', function () {
    $manager = app(BoricaManager::class);

    $url = $manager->getGatewayUrl();

    expect($url)->toBe('https://3dsgate-dev.borica.bg/cgi-bin/cgi_link');
});

test('proxies createPaymentRequest to default merchant', function () {
    $manager = app(BoricaManager::class);

    $request = $manager->createPaymentRequest(
        amount: '10.50',
        order: '000001',
        description: 'Test payment',
        mInfo: [
            'cardholderName' => 'John Doe',
            'email' => 'john@example.com',
        ],
    );

    expect($request)->toBeInstanceOf(PaymentRequest::class);
    expect($request->toArray()['AMOUNT'])->toBe('10.50');
});

test('resolves private key from file path', function () {
    config()->set('borica.merchants.file-based', [
        'terminal' => 'V1800001',
        'merchant_id' => 'MERCHANT01',
        'merchant_name' => 'File Shop',
        'private_key' => __DIR__ . '/../fixtures/test_private_key.pem',
        'private_key_passphrase' => null,
        'borica_public_key' => null,
        'environment' => 'development',
        'currency' => 'EUR',
        'country' => 'BG',
        'timezone_offset' => '+03',
    ]);

    $manager = app(BoricaManager::class);
    $borica = $manager->merchant('file-based');

    expect($borica)->toBeInstanceOf(Borica::class);
});

test('resolves private key from raw PEM string', function () {
    config()->set('borica.merchants.pem-based', [
        'terminal' => 'V1800001',
        'merchant_id' => 'MERCHANT01',
        'merchant_name' => 'PEM Shop',
        'private_key' => file_get_contents(__DIR__ . '/../fixtures/test_private_key.pem'),
        'private_key_passphrase' => null,
        'borica_public_key' => null,
        'environment' => 'development',
        'currency' => 'EUR',
        'country' => 'BG',
        'timezone_offset' => '+03',
    ]);

    $manager = app(BoricaManager::class);
    $borica = $manager->merchant('pem-based');

    expect($borica)->toBeInstanceOf(Borica::class);
});

test('throws exception for unknown merchant name', function () {
    $manager = app(BoricaManager::class);

    $manager->merchant('nonexistent');
})->throws(InvalidArgumentException::class, 'Borica merchant [nonexistent] is not configured');

test('resolves merchant by terminal ID', function () {
    $manager = app(BoricaManager::class);

    $borica = $manager->merchantByTerminal('V1800001');

    expect($borica)->toBeInstanceOf(Borica::class);
});

test('merchantByTerminal returns null for unknown terminal', function () {
    $manager = app(BoricaManager::class);

    $result = $manager->merchantByTerminal('UNKNOWN1');

    expect($result)->toBeNull();
});

test('finds merchant config name by terminal', function () {
    $manager = app(BoricaManager::class);

    $name = $manager->findMerchantNameByTerminal('V1800001');

    expect($name)->toBe('default');
});

test('resolveTerminalUsing resolves merchant from custom callback', function () {
    $manager = app(BoricaManager::class);

    $manager->resolveTerminalUsing(function (string $terminal): ?array {
        if ($terminal !== 'DBTERMN1') {
            return null;
        }

        return [
            'name' => 'tenant-a',
            'terminal' => 'DBTERMN1',
            'merchant_id' => 'DBMERCH001',
            'merchant_name' => 'DB Tenant A',
            'private_key' => file_get_contents(__DIR__ . '/../fixtures/test_private_key.pem'),
            'environment' => 'development',
            'currency' => 'EUR',
        ];
    });

    $borica = $manager->merchantByTerminal('DBTERMN1');

    expect($borica)->toBeInstanceOf(Borica::class);
    expect($borica->getGatewayUrl())->toBe('https://3dsgate-dev.borica.bg/cgi-bin/cgi_link');
});

test('resolveTerminalUsing returns name from config', function () {
    $manager = app(BoricaManager::class);

    $manager->resolveTerminalUsing(fn (string $terminal) => $terminal === 'DBTERMN1' ? [
        'name' => 'tenant-a',
        'terminal' => 'DBTERMN1',
        'merchant_id' => 'DBMERCH001',
        'merchant_name' => 'DB Tenant A',
        'private_key' => file_get_contents(__DIR__ . '/../fixtures/test_private_key.pem'),
        'environment' => 'development',
        'currency' => 'EUR',
    ] : null);

    $name = $manager->findMerchantNameByTerminal('DBTERMN1');

    expect($name)->toBe('tenant-a');
});

test('resolveTerminalUsing defaults name to terminal when not provided', function () {
    $manager = app(BoricaManager::class);

    $manager->resolveTerminalUsing(fn (string $terminal) => $terminal === 'DBTERMN1' ? [
        'terminal' => 'DBTERMN1',
        'merchant_id' => 'DBMERCH001',
        'merchant_name' => 'DB Tenant A',
        'private_key' => file_get_contents(__DIR__ . '/../fixtures/test_private_key.pem'),
        'environment' => 'development',
        'currency' => 'EUR',
    ] : null);

    $name = $manager->findMerchantNameByTerminal('DBTERMN1');

    expect($name)->toBe('DBTERMN1');
});

test('resolveTerminalUsing returns null for unmatched terminal', function () {
    $manager = app(BoricaManager::class);

    $manager->resolveTerminalUsing(fn () => null);

    expect($manager->merchantByTerminal('UNKNOWN1'))->toBeNull();
});

test('static config takes precedence over terminal resolver', function () {
    $manager = app(BoricaManager::class);

    $resolverCalled = false;
    $manager->resolveTerminalUsing(function () use (&$resolverCalled) {
        $resolverCalled = true;
        return null;
    });

    $name = $manager->findMerchantNameByTerminal('V1800001');

    expect($name)->toBe('default');
    expect($resolverCalled)->toBeFalse();
});

test('resolved terminal merchant is cached on subsequent merchant() call', function () {
    $manager = app(BoricaManager::class);

    $callCount = 0;
    $manager->resolveTerminalUsing(function (string $terminal) use (&$callCount) {
        $callCount++;
        return $terminal === 'DBTERMN1' ? [
            'name' => 'tenant-a',
            'terminal' => 'DBTERMN1',
            'merchant_id' => 'DBMERCH001',
            'merchant_name' => 'DB Tenant A',
            'private_key' => file_get_contents(__DIR__ . '/../fixtures/test_private_key.pem'),
            'environment' => 'development',
            'currency' => 'EUR',
        ] : null;
    });

    $manager->findMerchantNameByTerminal('DBTERMN1');
    $first = $manager->merchant('tenant-a');
    $second = $manager->merchant('tenant-a');

    expect($first)->toBe($second);
    expect($callCount)->toBe(1);
});

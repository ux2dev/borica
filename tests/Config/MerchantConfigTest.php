<?php

declare(strict_types=1);

use Ux2Dev\Borica\Config\MerchantConfig;
use Ux2Dev\Borica\Enum\Currency;
use Ux2Dev\Borica\Enum\Environment;
use Ux2Dev\Borica\Exception\ConfigurationException;

beforeEach(function () {
    $this->privateKey = file_get_contents(__DIR__ . '/../fixtures/test_private_key.pem');
});

test('creates valid config with defaults', function () {
    $config = new MerchantConfig(
        terminal: '12345678',
        merchantId: 'MERCHANT01',
        merchantName: 'Test Shop',
        privateKey: $this->privateKey,
        environment: Environment::Development,
        currency: Currency::BGN,
    );

    expect($config->terminal)->toBe('12345678')
        ->and($config->merchantId)->toBe('MERCHANT01')
        ->and($config->merchantName)->toBe('Test Shop')
        ->and($config->environment)->toBe(Environment::Development)
        ->and($config->currency)->toBe(Currency::BGN)
        ->and($config->country)->toBe('BG')
        ->and($config->timezoneOffset)->toBe('+03')
        ->and($config->getPrivateKeyPassphrase())->toBeNull();
});

test('throws ConfigurationException on empty terminal', function () {
    new MerchantConfig(
        terminal: '',
        merchantId: 'MERCHANT01',
        merchantName: 'Test Shop',
        privateKey: $this->privateKey,
        environment: Environment::Development,
        currency: Currency::BGN,
    );
})->throws(ConfigurationException::class, 'terminal must be exactly 8 alphanumeric characters');

test('throws ConfigurationException on terminal with special characters', function () {
    new MerchantConfig(
        terminal: 'V180@001',
        merchantId: 'MERCHANT01',
        merchantName: 'Test Shop',
        privateKey: $this->privateKey,
        environment: Environment::Development,
        currency: Currency::BGN,
    );
})->throws(ConfigurationException::class, 'terminal must be exactly 8 alphanumeric characters');

test('throws ConfigurationException on terminal with wrong length', function () {
    new MerchantConfig(
        terminal: 'V18',
        merchantId: 'MERCHANT01',
        merchantName: 'Test Shop',
        privateKey: $this->privateKey,
        environment: Environment::Development,
        currency: Currency::BGN,
    );
})->throws(ConfigurationException::class, 'terminal must be exactly 8 alphanumeric characters');

test('throws ConfigurationException on empty merchantId', function () {
    new MerchantConfig(
        terminal: '12345678',
        merchantId: '',
        merchantName: 'Test Shop',
        privateKey: $this->privateKey,
        environment: Environment::Development,
        currency: Currency::BGN,
    );
})->throws(ConfigurationException::class, 'merchantId must be exactly 10 alphanumeric characters');

test('throws ConfigurationException on merchantId with wrong length', function () {
    new MerchantConfig(
        terminal: '12345678',
        merchantId: 'SHORT',
        merchantName: 'Test Shop',
        privateKey: $this->privateKey,
        environment: Environment::Development,
        currency: Currency::BGN,
    );
})->throws(ConfigurationException::class, 'merchantId must be exactly 10 alphanumeric characters');

test('throws ConfigurationException on empty merchantName', function () {
    new MerchantConfig(
        terminal: '12345678',
        merchantId: 'MERCHANT01',
        merchantName: '',
        privateKey: $this->privateKey,
        environment: Environment::Development,
        currency: Currency::BGN,
    );
})->throws(ConfigurationException::class, 'merchantName must not be empty');

test('throws ConfigurationException on empty privateKey', function () {
    new MerchantConfig(
        terminal: '12345678',
        merchantId: 'MERCHANT01',
        merchantName: 'Test Shop',
        privateKey: '',
        environment: Environment::Development,
        currency: Currency::BGN,
    );
})->throws(ConfigurationException::class, 'privateKey must not be empty');

test('throws LogicException on serialize', function () {
    $config = new MerchantConfig(
        terminal: '12345678',
        merchantId: 'MERCHANT01',
        merchantName: 'Test Shop',
        privateKey: $this->privateKey,
        environment: Environment::Development,
        currency: Currency::BGN,
    );

    serialize($config);
})->throws(\LogicException::class, 'MerchantConfig must not be serialized');

test('throws ConfigurationException on invalid private key', function () {
    new MerchantConfig(
        terminal: '12345678',
        merchantId: 'MERCHANT01',
        merchantName: 'Test Shop',
        privateKey: 'not-a-valid-pem-key',
        environment: Environment::Development,
        currency: Currency::BGN,
    );
})->throws(ConfigurationException::class, 'privateKey is not a valid PEM private key');

test('throws LogicException on unserialize', function () {
    $config = new MerchantConfig(
        terminal: '12345678',
        merchantId: 'MERCHANT01',
        merchantName: 'Test Shop',
        privateKey: $this->privateKey,
        environment: Environment::Development,
        currency: Currency::BGN,
    );

    $config->__unserialize([]);
})->throws(\LogicException::class, 'MerchantConfig must not be unserialized');

test('debugInfo redacts private key and passphrase', function () {
    $config = new MerchantConfig(
        terminal: '12345678',
        merchantId: 'MERCHANT01',
        merchantName: 'Test Shop',
        privateKey: $this->privateKey,
        environment: Environment::Development,
        currency: Currency::BGN,
        privateKeyPassphrase: 'secret',
    );

    $debug = $config->__debugInfo();

    expect($debug['privateKey'])->toBe('[REDACTED]');
    expect($debug['privateKeyPassphrase'])->toBe('[REDACTED]');
    expect($debug['terminal'])->toBe('12345678');
    expect($debug['merchantId'])->toBe('MERCHANT01');
});

test('debugInfo shows null passphrase when not set', function () {
    $config = new MerchantConfig(
        terminal: '12345678',
        merchantId: 'MERCHANT01',
        merchantName: 'Test Shop',
        privateKey: $this->privateKey,
        environment: Environment::Development,
        currency: Currency::BGN,
    );

    $debug = $config->__debugInfo();

    expect($debug['privateKeyPassphrase'])->toBeNull();
});

test('custom country and timezone override defaults', function () {
    $config = new MerchantConfig(
        terminal: '12345678',
        merchantId: 'MERCHANT01',
        merchantName: 'Test Shop',
        privateKey: $this->privateKey,
        environment: Environment::Production,
        currency: Currency::EUR,
        country: 'DE',
        timezoneOffset: '+02',
        privateKeyPassphrase: 'secret',
    );

    expect($config->country)->toBe('DE')
        ->and($config->timezoneOffset)->toBe('+02')
        ->and($config->getPrivateKeyPassphrase())->toBe('secret');
});

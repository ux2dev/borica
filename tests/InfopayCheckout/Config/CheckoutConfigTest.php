<?php

declare(strict_types=1);

use Ux2Dev\Borica\Exception\ConfigurationException;
use Ux2Dev\Borica\InfopayCheckout\Config\CheckoutConfig;

beforeEach(function () {
    $this->privateKey = file_get_contents(__DIR__ . '/../../fixtures/test_private_key.pem');
    $this->certificate = file_get_contents(__DIR__ . '/../../fixtures/test_certificate.pem');
});

test('CheckoutConfig accepts inline PEM private key and certificate', function () {
    $cfg = new CheckoutConfig(
        baseUrl: 'https://uat-api-checkout.infopay.bg',
        authId: 'auth-id',
        authSecret: 'auth-secret',
        shopId: 'shop-id',
        privateKey: $this->privateKey,
        certificate: $this->certificate,
    );

    expect($cfg->getPrivateKey())->toBe($this->privateKey);
    expect($cfg->getCertificate())->toBe($this->certificate);
});

test('CheckoutConfig rejects empty base URL', function () {
    new CheckoutConfig(
        baseUrl: '',
        authId: 'a',
        authSecret: 'b',
        shopId: 'c',
        privateKey: $this->privateKey,
        certificate: $this->certificate,
    );
})->throws(ConfigurationException::class);

test('CheckoutConfig rejects base URL without https', function () {
    new CheckoutConfig(
        baseUrl: 'http://api.infopay.bg',
        authId: 'a',
        authSecret: 'b',
        shopId: 'c',
        privateKey: $this->privateKey,
        certificate: $this->certificate,
    );
})->throws(ConfigurationException::class);

test('CheckoutConfig rejects invalid private key', function () {
    new CheckoutConfig(
        baseUrl: 'https://api.infopay.bg',
        authId: 'a',
        authSecret: 'b',
        shopId: 'c',
        privateKey: 'not a key',
        certificate: $this->certificate,
    );
})->throws(ConfigurationException::class);

test('CheckoutConfig rejects invalid certificate', function () {
    new CheckoutConfig(
        baseUrl: 'https://api.infopay.bg',
        authId: 'a',
        authSecret: 'b',
        shopId: 'c',
        privateKey: $this->privateKey,
        certificate: 'not a certificate',
    );
})->throws(ConfigurationException::class);

test('CheckoutConfig trims trailing slash from base URL', function () {
    $cfg = new CheckoutConfig(
        baseUrl: 'https://uat-api-checkout.infopay.bg/',
        authId: 'a',
        authSecret: 'b',
        shopId: 'c',
        privateKey: $this->privateKey,
        certificate: $this->certificate,
    );
    expect($cfg->baseUrl)->toBe('https://uat-api-checkout.infopay.bg');
});

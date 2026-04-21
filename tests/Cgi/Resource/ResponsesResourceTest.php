<?php

declare(strict_types=1);

use Psr\Log\NullLogger;
use Ux2Dev\Borica\Cgi\Resource\ResponsesResource;
use Ux2Dev\Borica\Cgi\Response\Response;
use Ux2Dev\Borica\Config\MerchantConfig;
use Ux2Dev\Borica\Enum\Currency;
use Ux2Dev\Borica\Enum\Environment;
use Ux2Dev\Borica\Enum\TransactionType;
use Ux2Dev\Borica\Signing\MacGeneral;
use Ux2Dev\Borica\Signing\Signer;

beforeEach(function () {
    $privateKey = file_get_contents(__DIR__ . '/../../fixtures/test_private_key.pem');
    $this->publicKey = file_get_contents(__DIR__ . '/../../fixtures/test_public_key.pem');

    $config = new MerchantConfig(
        terminal: 'V1800001',
        merchantId: 'MERCHANT01',
        merchantName: 'Test Shop',
        privateKey: $privateKey,
        environment: Environment::Development,
        currency: Currency::EUR,
        country: 'BG',
        timezoneOffset: '+03',
    );

    $this->resource = new ResponsesResource(
        $config,
        new MacGeneral(),
        new Signer(),
        new NullLogger(),
    );

    $macGeneral = new MacGeneral();
    $signer = new Signer();

    $this->callbackData = [
        'ACTION' => '0',
        'RC' => '00',
        'APPROVAL' => '123456',
        'TERMINAL' => 'V1800001',
        'TRTYPE' => '1',
        'AMOUNT' => '10.50',
        'CURRENCY' => 'EUR',
        'ORDER' => '000001',
        'RRN' => '000000000001',
        'INT_REF' => 'ABCDEF123456',
        'PARES_STATUS' => 'Y',
        'ECI' => '05',
        'TIMESTAMP' => '20260420120000',
        'NONCE' => str_repeat('A', 32),
    ];

    $signingData = $macGeneral->buildResponseSigningData($this->callbackData);
    $this->callbackData['P_SIGN'] = $signer->sign($signingData, $privateKey);
});

test('parse returns Response when callback verifies against explicit public key', function () {
    $response = $this->resource->parse(
        $this->callbackData,
        TransactionType::Purchase,
        $this->publicKey,
    );

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getOrder())->toBe('000001');
});

test('parse returns successful Response for approved purchase', function () {
    $response = $this->resource->parse(
        $this->callbackData,
        TransactionType::Purchase,
        $this->publicKey,
    );

    expect($response->isSuccessful())->toBeTrue();
    expect($response->getApproval())->toBe('123456');
});

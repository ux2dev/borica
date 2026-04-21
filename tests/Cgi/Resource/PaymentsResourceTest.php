<?php

declare(strict_types=1);

use Psr\Log\NullLogger;
use Ux2Dev\Borica\Cgi\Request\PaymentRequest;
use Ux2Dev\Borica\Cgi\Request\ReversalRequest;
use Ux2Dev\Borica\Cgi\Resource\PaymentsResource;
use Ux2Dev\Borica\Config\MerchantConfig;
use Ux2Dev\Borica\Enum\Currency;
use Ux2Dev\Borica\Enum\Environment;
use Ux2Dev\Borica\Enum\TransactionType;
use Ux2Dev\Borica\Exception\ConfigurationException;
use Ux2Dev\Borica\Signing\MacGeneral;
use Ux2Dev\Borica\Signing\Signer;

beforeEach(function () {
    $privateKey = file_get_contents(__DIR__ . '/../../fixtures/test_private_key.pem');

    $this->config = new MerchantConfig(
        terminal: 'V1800001',
        merchantId: 'MERCHANT01',
        merchantName: 'Test Shop',
        privateKey: $privateKey,
        environment: Environment::Development,
        currency: Currency::EUR,
        country: 'BG',
        timezoneOffset: '+03',
    );

    $this->resource = new PaymentsResource(
        $this->config,
        new MacGeneral(),
        new Signer(),
        new NullLogger(),
    );
});

test('purchase returns signed PaymentRequest', function () {
    $req = $this->resource->purchase(
        amount: '10.50',
        order: '000001',
        description: 'Test payment',
        mInfo: ['cardholderName' => 'John Doe', 'email' => 'j@e.com'],
    );

    expect($req)->toBeInstanceOf(PaymentRequest::class);
    expect($req->getTransactionType())->toBe(TransactionType::Purchase);

    $data = $req->toArray();
    expect($data['AMOUNT'])->toBe('10.50');
    expect($data['ORDER'])->toBe('000001');
    expect($data['MERCHANT'])->toBe('MERCHANT01');
    expect($data['TERMINAL'])->toBe('V1800001');
    expect($data['P_SIGN'])->not->toBe('');
});

test('purchase rejects invalid amount', function () {
    $this->resource->purchase(
        amount: 'invalid',
        order: '000001',
        description: 'x',
        mInfo: ['cardholderName' => 'John', 'email' => 'j@e.com'],
    );
})->throws(ConfigurationException::class);

test('reverse returns signed ReversalRequest', function () {
    $req = $this->resource->reverse(
        amount: '10.50',
        order: '000001',
        rrn: '000000000001',
        intRef: 'ABC123',
        description: 'Refund',
    );

    expect($req)->toBeInstanceOf(ReversalRequest::class);

    $data = $req->toArray();
    expect($data['RRN'])->toBe('000000000001');
    expect($data['INT_REF'])->toBe('ABC123');
    expect($data['P_SIGN'])->not->toBe('');
});

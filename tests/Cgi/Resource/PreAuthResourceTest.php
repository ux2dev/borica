<?php

declare(strict_types=1);

use Psr\Log\NullLogger;
use Ux2Dev\Borica\Cgi\Request\PreAuthCompleteRequest;
use Ux2Dev\Borica\Cgi\Request\PreAuthRequest;
use Ux2Dev\Borica\Cgi\Request\PreAuthReversalRequest;
use Ux2Dev\Borica\Cgi\Resource\PreAuthResource;
use Ux2Dev\Borica\Config\MerchantConfig;
use Ux2Dev\Borica\Enum\Currency;
use Ux2Dev\Borica\Enum\Environment;
use Ux2Dev\Borica\Enum\TransactionType;
use Ux2Dev\Borica\Signing\MacGeneral;
use Ux2Dev\Borica\Signing\Signer;

beforeEach(function () {
    $privateKey = file_get_contents(__DIR__ . '/../../fixtures/test_private_key.pem');

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

    $this->resource = new PreAuthResource(
        $config,
        new MacGeneral(),
        new Signer(),
        new NullLogger(),
    );
});

test('create returns signed PreAuthRequest with TR 12', function () {
    $req = $this->resource->create(
        amount: '10.50',
        order: '000001',
        description: 'Preauth',
        mInfo: ['cardholderName' => 'John Doe', 'email' => 'j@e.com'],
    );

    expect($req)->toBeInstanceOf(PreAuthRequest::class);
    expect($req->getTransactionType())->toBe(TransactionType::PreAuth);

    $data = $req->toArray();
    expect($data['P_SIGN'])->not->toBe('');
});

test('complete returns signed PreAuthCompleteRequest', function () {
    $req = $this->resource->complete(
        amount: '10.50',
        order: '000001',
        rrn: '000000000001',
        intRef: 'ABC123',
        description: 'Complete',
    );

    expect($req)->toBeInstanceOf(PreAuthCompleteRequest::class);

    $data = $req->toArray();
    expect($data['RRN'])->toBe('000000000001');
    expect($data['P_SIGN'])->not->toBe('');
});

test('reverse returns signed PreAuthReversalRequest', function () {
    $req = $this->resource->reverse(
        amount: '10.50',
        order: '000001',
        rrn: '000000000001',
        intRef: 'ABC123',
        description: 'Reverse preauth',
    );

    expect($req)->toBeInstanceOf(PreAuthReversalRequest::class);

    $data = $req->toArray();
    expect($data['RRN'])->toBe('000000000001');
    expect($data['P_SIGN'])->not->toBe('');
});

<?php

declare(strict_types=1);

use Psr\Log\NullLogger;
use Ux2Dev\Borica\Cgi\Request\StatusCheckRequest;
use Ux2Dev\Borica\Cgi\Resource\StatusResource;
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

    $this->resource = new StatusResource(
        $config,
        new MacGeneral(),
        new Signer(),
        new NullLogger(),
    );
});

test('check returns signed StatusCheckRequest with specified trtype', function () {
    $req = $this->resource->check('000001', TransactionType::Purchase);

    expect($req)->toBeInstanceOf(StatusCheckRequest::class);

    $data = $req->toArray();
    expect($data['ORDER'])->toBe('000001');
    expect($data['TRAN_TRTYPE'])->toBe((string) TransactionType::Purchase->value);
    expect($data['P_SIGN'])->not->toBe('');
});

test('check uses provided nonce when given', function () {
    $nonce = str_repeat('A', 32);
    $req = $this->resource->check('000001', TransactionType::PreAuth, nonce: $nonce);

    $data = $req->toArray();
    expect($data['NONCE'])->toBe($nonce);
});

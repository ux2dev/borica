<?php

declare(strict_types=1);

use Ux2Dev\Borica\Borica;
use Ux2Dev\Borica\Config\MerchantConfig;
use Ux2Dev\Borica\Enum\Currency;
use Ux2Dev\Borica\Enum\Environment;
use Ux2Dev\Borica\Enum\TransactionType;
use Ux2Dev\Borica\Request\PaymentRequest;
use Ux2Dev\Borica\Request\ReversalRequest;
use Ux2Dev\Borica\Request\StatusCheckRequest;
use Ux2Dev\Borica\Response\Response;
use Ux2Dev\Borica\Signing\MacGeneral;
use Ux2Dev\Borica\Signing\Signer;

beforeEach(function () {
    $this->privateKey = file_get_contents(__DIR__ . '/fixtures/test_private_key.pem');
    $this->publicKey = file_get_contents(__DIR__ . '/fixtures/test_public_key.pem');

    $this->config = new MerchantConfig(
        terminal: 'V1800001',
        merchantId: 'MERCHANT001',
        merchantName: 'Test Shop',
        privateKey: $this->privateKey,
        environment: Environment::Development,
        currency: Currency::BGN,
        country: 'BG',
        timezoneOffset: '+03',
    );

    $this->borica = new Borica($this->config);
});

test('getGatewayUrl returns development URL', function () {
    expect($this->borica->getGatewayUrl())->toBe('https://3dsgate-dev.borica.bg/cgi-bin/cgi_link');
});

test('createPaymentRequest returns PaymentRequest with correct fields', function () {
    $request = $this->borica->createPaymentRequest(
        amount: '9.00',
        order: '000001',
        description: 'Test payment',
        timestamp: '20201012124757',
        nonce: 'AABBCCDDAABBCCDDAABBCCDDAABBCCDD',
    );

    expect($request)->toBeInstanceOf(PaymentRequest::class);

    $data = $request->toArray();

    expect($data['TERMINAL'])->toBe('V1800001');
    expect($data['TRTYPE'])->toBe('1');
    expect($data['AMOUNT'])->toBe('9.00');
    expect($data['CURRENCY'])->toBe('BGN');
    expect($data['MERCHANT'])->toBe('MERCHANT001');
    expect($data['MERCH_NAME'])->toBe('Test Shop');
    expect($data)->toHaveKey('P_SIGN');
    expect($data['P_SIGN'])->toHaveLength(512)->toMatch('/^[A-F0-9]{512}$/');
    expect($data['TIMESTAMP'])->toHaveLength(14);
    expect($data['NONCE'])->toHaveLength(32)->toMatch('/^[A-F0-9]{32}$/');
});

test('createPaymentRequest auto-generates timestamp and nonce', function () {
    $request = $this->borica->createPaymentRequest(
        amount: '9.00',
        order: '000001',
        description: 'Test payment',
    );

    $data = $request->toArray();

    expect($data['TIMESTAMP'])->toHaveLength(14)->toMatch('/^\d{14}$/');
    expect($data['NONCE'])->toHaveLength(32)->toMatch('/^[A-F0-9]{32}$/');
});

test('createStatusCheckRequest returns StatusCheckRequest with correct fields', function () {
    $request = $this->borica->createStatusCheckRequest(
        order: '000001',
        transactionType: TransactionType::Purchase,
        nonce: 'AABBCCDDAABBCCDDAABBCCDDAABBCCDD',
    );

    expect($request)->toBeInstanceOf(StatusCheckRequest::class);

    $data = $request->toArray();

    expect($data['TRTYPE'])->toBe('90');
    expect($data['TRAN_TRTYPE'])->toBe('1');
    expect($data)->toHaveKey('P_SIGN');
    expect($data['P_SIGN'])->toHaveLength(512)->toMatch('/^[A-F0-9]{512}$/');
});

test('createReversalRequest returns ReversalRequest with correct fields', function () {
    $request = $this->borica->createReversalRequest(
        amount: '9.00',
        order: '000001',
        rrn: '012345678901',
        intRef: 'ABCDEF123456',
        description: 'Test reversal',
        timestamp: '20201012124757',
        nonce: 'AABBCCDDAABBCCDDAABBCCDDAABBCCDD',
    );

    expect($request)->toBeInstanceOf(ReversalRequest::class);

    $data = $request->toArray();

    expect($data['TRTYPE'])->toBe('24');
    expect($data['RRN'])->toBe('012345678901');
    expect($data['INT_REF'])->toBe('ABCDEF123456');
    expect($data)->toHaveKey('P_SIGN');
    expect($data['P_SIGN'])->toHaveLength(512)->toMatch('/^[A-F0-9]{512}$/');
});

test('full round-trip: create payment request, sign mock response, parse with test public key', function () {
    $request = $this->borica->createPaymentRequest(
        amount: '9.00',
        order: '000001',
        description: 'Test payment',
        timestamp: '20201012124757',
        nonce: 'AABBCCDDAABBCCDDAABBCCDDAABBCCDD',
    );

    $requestData = $request->toArray();

    $responseFields = [
        'ACTION' => '0',
        'RC' => '00',
        'APPROVAL' => '123456',
        'TERMINAL' => $requestData['TERMINAL'],
        'TRTYPE' => $requestData['TRTYPE'],
        'AMOUNT' => $requestData['AMOUNT'],
        'CURRENCY' => $requestData['CURRENCY'],
        'ORDER' => $requestData['ORDER'],
        'RRN' => '012345678901',
        'INT_REF' => 'ABCDEF123456',
        'PARES_STATUS' => 'Y',
        'ECI' => '05',
        'TIMESTAMP' => $requestData['TIMESTAMP'],
        'NONCE' => $requestData['NONCE'],
    ];

    $macGeneral = new MacGeneral();
    $signer = new Signer();
    $signingData = $macGeneral->buildResponseSigningData(TransactionType::Purchase, $responseFields);
    $responseFields['P_SIGN'] = $signer->sign($signingData, $this->privateKey);

    $response = $this->borica->parseResponse(
        $responseFields,
        TransactionType::Purchase,
        $this->publicKey,
    );

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->isSuccessful())->toBeTrue();
    expect($response->getOrder())->toBe('000001');
    expect($response->getApproval())->toBe('123456');
});

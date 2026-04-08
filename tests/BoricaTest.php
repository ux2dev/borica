<?php

declare(strict_types=1);

use Ux2Dev\Borica\Borica;
use Ux2Dev\Borica\Config\MerchantConfig;
use Ux2Dev\Borica\Enum\Currency;
use Ux2Dev\Borica\Enum\Environment;
use Ux2Dev\Borica\Enum\TransactionType;
use Ux2Dev\Borica\Exception\ConfigurationException;
use Ux2Dev\Borica\Request\PaymentRequest;
use Ux2Dev\Borica\Request\PreAuthCompleteRequest;
use Ux2Dev\Borica\Request\PreAuthRequest;
use Ux2Dev\Borica\Request\PreAuthReversalRequest;
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
        merchantId: 'MERCHANT01',
        merchantName: 'Test Shop',
        privateKey: $this->privateKey,
        environment: Environment::Development,
        currency: Currency::EUR,
        country: 'BG',
        timezoneOffset: '+03',
    );

    $this->borica = new Borica($this->config);
});

test('getGatewayUrl returns development URL', function () {
    expect($this->borica->getGatewayUrl())->toBe('https://3dsgate-dev.borica.bg/cgi-bin/cgi_link');
});

test('createPaymentRequest returns PaymentRequest with correct fields', function () {
    $timestamp = gmdate('YmdHis');
    $request = $this->borica->createPaymentRequest(
        amount: '9.00',
        order: '000001',
        description: 'Test payment',
        mInfo: ['cardholderName' => 'John Doe', 'email' => 'john@example.com'],
        timestamp: $timestamp,
        nonce: 'AABBCCDDAABBCCDDAABBCCDDAABBCCDD',
    );

    expect($request)->toBeInstanceOf(PaymentRequest::class);

    $data = $request->toArray();

    expect($data['TERMINAL'])->toBe('V1800001');
    expect($data['TRTYPE'])->toBe('1');
    expect($data['AMOUNT'])->toBe('9.00');
    expect($data['CURRENCY'])->toBe('EUR');
    expect($data['MERCHANT'])->toBe('MERCHANT01');
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
        mInfo: ['cardholderName' => 'John Doe', 'email' => 'john@example.com'],
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
    $timestamp = gmdate('YmdHis');
    $request = $this->borica->createReversalRequest(
        amount: '9.00',
        order: '000001',
        rrn: '012345678901',
        intRef: 'ABCDEF123456',
        description: 'Test reversal',
        timestamp: $timestamp,
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
    $timestamp = gmdate('YmdHis');
    $nonce = 'AABBCCDDAABBCCDDAABBCCDDAABBCCDD';

    $request = $this->borica->createPaymentRequest(
        amount: '9.00',
        order: '000001',
        description: 'Test payment',
        mInfo: ['cardholderName' => 'John Doe', 'email' => 'john@example.com'],
        timestamp: $timestamp,
        nonce: $nonce,
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
    $signingData = $macGeneral->buildResponseSigningData($responseFields);
    $responseFields['P_SIGN'] = $signer->sign($signingData, $this->privateKey);

    $response = $this->borica->parseResponse(
        $responseFields,
        TransactionType::Purchase,
        publicKey: $this->publicKey,
    );

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->isSuccessful())->toBeTrue();
    expect($response->getOrder())->toBe('000001');
    expect($response->getApproval())->toBe('123456');
});

test('rejects negative amount', function () {
    $this->borica->createPaymentRequest(
        amount: '-10.00',
        order: '000001',
        description: 'Test',
        mInfo: ['cardholderName' => 'John Doe', 'email' => 'john@example.com'],
    );
})->throws(ConfigurationException::class, 'Amount must be a positive number with exactly 2 decimal places');

test('rejects zero amount', function () {
    $this->borica->createPaymentRequest(
        amount: '0.00',
        order: '000001',
        description: 'Test',
        mInfo: ['cardholderName' => 'John Doe', 'email' => 'john@example.com'],
    );
})->throws(ConfigurationException::class, 'Amount must be greater than zero');

test('rejects amount without two decimal places', function () {
    $this->borica->createPaymentRequest(
        amount: '10',
        order: '000001',
        description: 'Test',
        mInfo: ['cardholderName' => 'John Doe', 'email' => 'john@example.com'],
    );
})->throws(ConfigurationException::class, 'Amount must be a positive number with exactly 2 decimal places');

test('rejects non-numeric order', function () {
    $this->borica->createPaymentRequest(
        amount: '10.00',
        order: 'abc',
        description: 'Test',
        mInfo: ['cardholderName' => 'John Doe', 'email' => 'john@example.com'],
    );
})->throws(ConfigurationException::class, 'Order must be exactly 6 digits');

test('rejects invalid nonce format', function () {
    $this->borica->createPaymentRequest(
        amount: '10.00',
        order: '000001',
        description: 'Test',
        mInfo: ['cardholderName' => 'John Doe', 'email' => 'john@example.com'],
        nonce: 'short',
    );
})->throws(ConfigurationException::class, 'Nonce must be exactly 32 uppercase hex characters');

test('rejects invalid timestamp format', function () {
    $this->borica->createPaymentRequest(
        amount: '10.00',
        order: '000001',
        description: 'Test',
        mInfo: ['cardholderName' => 'John Doe', 'email' => 'john@example.com'],
        timestamp: 'not-a-timestamp',
    );
})->throws(ConfigurationException::class, 'Timestamp must be exactly 14 digits');

test('rejects empty description', function () {
    $this->borica->createPaymentRequest(
        amount: '10.00',
        order: '000001',
        description: '',
        mInfo: ['cardholderName' => 'John Doe', 'email' => 'john@example.com'],
    );
})->throws(ConfigurationException::class, 'Description must not be empty');

test('rejects description exceeding 50 characters', function () {
    $this->borica->createPaymentRequest(
        amount: '10.00',
        order: '000001',
        description: str_repeat('A', 51),
        mInfo: ['cardholderName' => 'John Doe', 'email' => 'john@example.com'],
    );
})->throws(ConfigurationException::class, 'Description must not exceed 50 characters');

test('rejects invalid email format', function () {
    $this->borica->createPaymentRequest(
        amount: '10.00',
        order: '000001',
        description: 'Test',
        mInfo: ['cardholderName' => 'John Doe', 'email' => 'john@example.com'],
        email: 'not-an-email',
    );
})->throws(ConfigurationException::class, 'Invalid email format');

test('rejects non-HTTPS merchant URL', function () {
    $this->borica->createPaymentRequest(
        amount: '10.00',
        order: '000001',
        description: 'Test',
        mInfo: ['cardholderName' => 'John Doe', 'email' => 'john@example.com'],
        merchantUrl: 'http://ux2.dev/callback',
    );
})->throws(ConfigurationException::class, 'Merchant URL must use HTTPS');

test('rejects invalid merchant URL', function () {
    $this->borica->createPaymentRequest(
        amount: '10.00',
        order: '000001',
        description: 'Test',
        mInfo: ['cardholderName' => 'John Doe', 'email' => 'john@example.com'],
        merchantUrl: 'not-a-url',
    );
})->throws(ConfigurationException::class, 'Invalid merchant URL');

test('accepts valid email and HTTPS merchant URL', function () {
    $request = $this->borica->createPaymentRequest(
        amount: '10.00',
        order: '000001',
        description: 'Test',
        mInfo: ['cardholderName' => 'John Doe', 'email' => 'john@example.com'],
        email: 'user@ux2.dev',
        merchantUrl: 'https://shop.ux2.dev/callback',
    );

    $data = $request->toArray();
    expect($data['EMAIL'])->toBe('user@ux2.dev');
    expect($data['MERCH_URL'])->toBe('https://shop.ux2.dev/callback');
});

test('rejects oversized mInfo', function () {
    $hugeArray = ['cardholderName' => 'John Doe', 'email' => 'john@example.com'];
    for ($i = 0; $i < 3000; $i++) {
        $hugeArray["key_$i"] = str_repeat('x', 50);
    }
    $this->borica->createPaymentRequest(
        amount: '10.00',
        order: '000001',
        description: 'Test',
        mInfo: $hugeArray,
    );
})->throws(ConfigurationException::class, 'M_INFO data exceeds maximum allowed size');

test('AD.CUST_BOR_ORDER_ID defaults to ORDER when not provided', function () {
    $request = $this->borica->createPaymentRequest(
        amount: '10.00',
        order: '000001',
        description: 'Test',
        mInfo: ['cardholderName' => 'John Doe', 'email' => 'john@example.com'],
    );

    $data = $request->toArray();
    expect($data['AD.CUST_BOR_ORDER_ID'])->toBe('000001');
    expect($data['ADDENDUM'])->toBe('AD,TD');
});

test('AD.CUST_BOR_ORDER_ID strips semicolons and truncates to 22 chars', function () {
    $request = $this->borica->createPaymentRequest(
        amount: '10.00',
        order: '000001',
        description: 'Test',
        mInfo: ['cardholderName' => 'John Doe', 'email' => 'john@example.com'],
        adCustBorOrderId: 'ORDER;WITH;SEMICOLONS;AND;VERY;LONG;VALUE',
    );

    $data = $request->toArray();
    expect($data['AD.CUST_BOR_ORDER_ID'])->toBe('ORDERWITHSEMICOLONSAND')
        ->toHaveLength(22)
        ->not->toContain(';');
});

test('rejects order with fewer than 6 digits', function () {
    $this->borica->createPaymentRequest(
        amount: '10.00',
        order: '123',
        description: 'Test',
        mInfo: ['cardholderName' => 'John Doe', 'email' => 'john@example.com'],
    );
})->throws(ConfigurationException::class, 'Order must be exactly 6 digits');

test('rejects mInfo without cardholderName', function () {
    $this->borica->createPaymentRequest(
        amount: '10.00',
        order: '000001',
        description: 'Test',
        mInfo: ['email' => 'john@example.com'],
    );
})->throws(ConfigurationException::class, 'M_INFO must contain a non-empty "cardholderName"');

test('rejects mInfo without email or phone', function () {
    $this->borica->createPaymentRequest(
        amount: '10.00',
        order: '000001',
        description: 'Test',
        mInfo: ['cardholderName' => 'John Doe'],
    );
})->throws(ConfigurationException::class, 'M_INFO must contain "email" and/or "mobilePhone"');

test('rejects mInfo with cardholderName exceeding 45 chars', function () {
    $this->borica->createPaymentRequest(
        amount: '10.00',
        order: '000001',
        description: 'Test',
        mInfo: ['cardholderName' => str_repeat('A', 46), 'email' => 'john@example.com'],
    );
})->throws(ConfigurationException::class, 'M_INFO "cardholderName" must not exceed 45 characters');

test('accepts mInfo with phone only', function () {
    $request = $this->borica->createPaymentRequest(
        amount: '10.00',
        order: '000001',
        description: 'Test',
        mInfo: ['cardholderName' => 'John Doe', 'mobilePhone' => ['cc' => '359', 'subscriber' => '888123456']],
    );

    expect($request->toArray())->toHaveKey('M_INFO');
});

test('createPreAuthRequest returns PreAuthRequest with correct fields', function () {
    $request = $this->borica->createPreAuthRequest(
        amount: '50.00',
        order: '000002',
        description: 'Pre-auth test',
        mInfo: ['cardholderName' => 'John Doe', 'email' => 'john@example.com'],
    );

    expect($request)->toBeInstanceOf(PreAuthRequest::class);

    $data = $request->toArray();

    expect($data['TRTYPE'])->toBe('12');
    expect($data['AMOUNT'])->toBe('50.00');
    expect($data['CURRENCY'])->toBe('EUR');
    expect($data['P_SIGN'])->toHaveLength(512)->toMatch('/^[A-F0-9]{512}$/');
    expect($data)->toHaveKey('M_INFO');
    expect($data['ADDENDUM'])->toBe('AD,TD');
});

test('createPreAuthCompleteRequest returns PreAuthCompleteRequest with correct fields', function () {
    $request = $this->borica->createPreAuthCompleteRequest(
        amount: '50.00',
        order: '000002',
        rrn: '012345678901',
        intRef: 'ABCDEF123456',
        description: 'Complete pre-auth',
    );

    expect($request)->toBeInstanceOf(PreAuthCompleteRequest::class);

    $data = $request->toArray();

    expect($data['TRTYPE'])->toBe('21');
    expect($data['AMOUNT'])->toBe('50.00');
    expect($data['RRN'])->toBe('012345678901');
    expect($data['INT_REF'])->toBe('ABCDEF123456');
    expect($data['P_SIGN'])->toHaveLength(512)->toMatch('/^[A-F0-9]{512}$/');
    expect($data['ADDENDUM'])->toBe('AD,TD');
});

test('createPreAuthReversalRequest returns PreAuthReversalRequest with correct fields', function () {
    $request = $this->borica->createPreAuthReversalRequest(
        amount: '50.00',
        order: '000002',
        rrn: '012345678901',
        intRef: 'ABCDEF123456',
        description: 'Reverse pre-auth',
    );

    expect($request)->toBeInstanceOf(PreAuthReversalRequest::class);

    $data = $request->toArray();

    expect($data['TRTYPE'])->toBe('22');
    expect($data['AMOUNT'])->toBe('50.00');
    expect($data['RRN'])->toBe('012345678901');
    expect($data['INT_REF'])->toBe('ABCDEF123456');
    expect($data['P_SIGN'])->toHaveLength(512)->toMatch('/^[A-F0-9]{512}$/');
    expect($data['ADDENDUM'])->toBe('AD,TD');
});

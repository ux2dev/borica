<?php

declare(strict_types=1);

use Ux2Dev\Borica\Cgi\CgiClient;
use Ux2Dev\Borica\Cgi\Request\PaymentRequest;
use Ux2Dev\Borica\Cgi\Request\PreAuthCompleteRequest;
use Ux2Dev\Borica\Cgi\Request\PreAuthRequest;
use Ux2Dev\Borica\Cgi\Request\PreAuthReversalRequest;
use Ux2Dev\Borica\Cgi\Request\ReversalRequest;
use Ux2Dev\Borica\Cgi\Request\StatusCheckRequest;
use Ux2Dev\Borica\Cgi\Resource\PaymentsResource;
use Ux2Dev\Borica\Cgi\Resource\PreAuthResource;
use Ux2Dev\Borica\Cgi\Resource\ResponsesResource;
use Ux2Dev\Borica\Cgi\Resource\StatusResource;
use Ux2Dev\Borica\Cgi\Response\Response;
use Ux2Dev\Borica\Config\MerchantConfig;
use Ux2Dev\Borica\Enum\Currency;
use Ux2Dev\Borica\Enum\Environment;
use Ux2Dev\Borica\Enum\TransactionType;
use Ux2Dev\Borica\Exception\ConfigurationException;
use Ux2Dev\Borica\Signing\MacGeneral;
use Ux2Dev\Borica\Signing\Signer;

beforeEach(function () {
    $privateKey = file_get_contents(__DIR__ . '/../fixtures/test_private_key.pem');

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

    $this->client = new CgiClient($this->config);
});

test('getGatewayUrl returns environment URL', function () {
    expect($this->client->getGatewayUrl())->toBe('https://3dsgate-dev.borica.bg/cgi-bin/cgi_link');
});

test('payments returns PaymentsResource', function () {
    expect($this->client->payments())->toBeInstanceOf(PaymentsResource::class);
});

test('preAuth returns PreAuthResource', function () {
    expect($this->client->preAuth())->toBeInstanceOf(PreAuthResource::class);
});

test('status returns StatusResource', function () {
    expect($this->client->status())->toBeInstanceOf(StatusResource::class);
});

test('responses returns ResponsesResource', function () {
    expect($this->client->responses())->toBeInstanceOf(ResponsesResource::class);
});

test('same resource instance returned on repeated calls', function () {
    expect($this->client->payments())->toBe($this->client->payments());
    expect($this->client->preAuth())->toBe($this->client->preAuth());
    expect($this->client->status())->toBe($this->client->status());
    expect($this->client->responses())->toBe($this->client->responses());
});

// ---------------------------------------------------------------------------
// payments()->purchase
// ---------------------------------------------------------------------------

test('payments()->purchase returns PaymentRequest with correct fields', function () {
    $timestamp = gmdate('YmdHis');
    $req = $this->client->payments()->purchase(
        amount: '9.00',
        order: '000001',
        description: 'Test payment',
        mInfo: ['cardholderName' => 'John Doe', 'email' => 'john@example.com'],
        timestamp: $timestamp,
        nonce: 'AABBCCDDAABBCCDDAABBCCDDAABBCCDD',
    );

    expect($req)->toBeInstanceOf(PaymentRequest::class);

    $data = $req->toArray();

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

test('payments()->purchase auto-generates timestamp and nonce', function () {
    $req = $this->client->payments()->purchase(
        amount: '9.00',
        order: '000001',
        description: 'Test payment',
        mInfo: ['cardholderName' => 'John Doe', 'email' => 'john@example.com'],
    );

    $data = $req->toArray();

    expect($data['TIMESTAMP'])->toHaveLength(14)->toMatch('/^\d{14}$/');
    expect($data['NONCE'])->toHaveLength(32)->toMatch('/^[A-F0-9]{32}$/');
});

test('payments()->purchase produces signed PaymentRequest end-to-end', function () {
    $req = $this->client->payments()->purchase(
        amount: '10.50',
        order: '000001',
        description: 'Test',
        mInfo: ['cardholderName' => 'John Doe', 'email' => 'j@e.com'],
    );
    expect($req)->toBeInstanceOf(PaymentRequest::class);
    $data = $req->toArray();
    expect($data['P_SIGN'])->not->toBe('');
    expect($data['AMOUNT'])->toBe('10.50');
    expect($data['ORDER'])->toBe('000001');
});

test('payments()->purchase rejects negative amount', function () {
    $this->client->payments()->purchase(
        amount: '-10.00',
        order: '000001',
        description: 'Test',
        mInfo: ['cardholderName' => 'John Doe', 'email' => 'john@example.com'],
    );
})->throws(ConfigurationException::class, 'Amount must be a positive number with exactly 2 decimal places');

test('payments()->purchase rejects zero amount', function () {
    $this->client->payments()->purchase(
        amount: '0.00',
        order: '000001',
        description: 'Test',
        mInfo: ['cardholderName' => 'John Doe', 'email' => 'john@example.com'],
    );
})->throws(ConfigurationException::class, 'Amount must be greater than zero');

test('payments()->purchase rejects amount without two decimal places', function () {
    $this->client->payments()->purchase(
        amount: '10',
        order: '000001',
        description: 'Test',
        mInfo: ['cardholderName' => 'John Doe', 'email' => 'john@example.com'],
    );
})->throws(ConfigurationException::class, 'Amount must be a positive number with exactly 2 decimal places');

test('payments()->purchase rejects non-numeric order', function () {
    $this->client->payments()->purchase(
        amount: '10.00',
        order: 'abc',
        description: 'Test',
        mInfo: ['cardholderName' => 'John Doe', 'email' => 'john@example.com'],
    );
})->throws(ConfigurationException::class, 'Order must be exactly 6 digits');

test('payments()->purchase rejects order with fewer than 6 digits', function () {
    $this->client->payments()->purchase(
        amount: '10.00',
        order: '123',
        description: 'Test',
        mInfo: ['cardholderName' => 'John Doe', 'email' => 'john@example.com'],
    );
})->throws(ConfigurationException::class, 'Order must be exactly 6 digits');

test('payments()->purchase rejects invalid nonce format', function () {
    $this->client->payments()->purchase(
        amount: '10.00',
        order: '000001',
        description: 'Test',
        mInfo: ['cardholderName' => 'John Doe', 'email' => 'john@example.com'],
        nonce: 'short',
    );
})->throws(ConfigurationException::class, 'Nonce must be exactly 32 uppercase hex characters');

test('payments()->purchase rejects invalid timestamp format', function () {
    $this->client->payments()->purchase(
        amount: '10.00',
        order: '000001',
        description: 'Test',
        mInfo: ['cardholderName' => 'John Doe', 'email' => 'john@example.com'],
        timestamp: 'not-a-timestamp',
    );
})->throws(ConfigurationException::class, 'Timestamp must be exactly 14 digits');

test('payments()->purchase rejects empty description', function () {
    $this->client->payments()->purchase(
        amount: '10.00',
        order: '000001',
        description: '',
        mInfo: ['cardholderName' => 'John Doe', 'email' => 'john@example.com'],
    );
})->throws(ConfigurationException::class, 'Description must not be empty');

test('payments()->purchase rejects description exceeding 50 characters', function () {
    $this->client->payments()->purchase(
        amount: '10.00',
        order: '000001',
        description: str_repeat('A', 51),
        mInfo: ['cardholderName' => 'John Doe', 'email' => 'john@example.com'],
    );
})->throws(ConfigurationException::class, 'Description must not exceed 50 characters');

test('payments()->purchase rejects invalid email format', function () {
    $this->client->payments()->purchase(
        amount: '10.00',
        order: '000001',
        description: 'Test',
        mInfo: ['cardholderName' => 'John Doe', 'email' => 'john@example.com'],
        email: 'not-an-email',
    );
})->throws(ConfigurationException::class, 'Invalid email format');

test('payments()->purchase rejects non-HTTPS merchant URL', function () {
    $this->client->payments()->purchase(
        amount: '10.00',
        order: '000001',
        description: 'Test',
        mInfo: ['cardholderName' => 'John Doe', 'email' => 'john@example.com'],
        merchantUrl: 'http://ux2.dev/callback',
    );
})->throws(ConfigurationException::class, 'Merchant URL must use HTTPS');

test('payments()->purchase rejects invalid merchant URL', function () {
    $this->client->payments()->purchase(
        amount: '10.00',
        order: '000001',
        description: 'Test',
        mInfo: ['cardholderName' => 'John Doe', 'email' => 'john@example.com'],
        merchantUrl: 'not-a-url',
    );
})->throws(ConfigurationException::class, 'Invalid merchant URL');

test('payments()->purchase accepts valid email and HTTPS merchant URL', function () {
    $req = $this->client->payments()->purchase(
        amount: '10.00',
        order: '000001',
        description: 'Test',
        mInfo: ['cardholderName' => 'John Doe', 'email' => 'john@example.com'],
        email: 'user@ux2.dev',
        merchantUrl: 'https://shop.ux2.dev/callback',
    );

    $data = $req->toArray();
    expect($data['EMAIL'])->toBe('user@ux2.dev');
    expect($data['MERCH_URL'])->toBe('https://shop.ux2.dev/callback');
});

test('payments()->purchase rejects oversized mInfo', function () {
    $hugeArray = ['cardholderName' => 'John Doe', 'email' => 'john@example.com'];
    for ($i = 0; $i < 3000; $i++) {
        $hugeArray["key_$i"] = str_repeat('x', 50);
    }
    $this->client->payments()->purchase(
        amount: '10.00',
        order: '000001',
        description: 'Test',
        mInfo: $hugeArray,
    );
})->throws(ConfigurationException::class, 'M_INFO data exceeds maximum allowed size');

test('payments()->purchase AD.CUST_BOR_ORDER_ID defaults to ORDER when not provided', function () {
    $req = $this->client->payments()->purchase(
        amount: '10.00',
        order: '000001',
        description: 'Test',
        mInfo: ['cardholderName' => 'John Doe', 'email' => 'john@example.com'],
    );

    $data = $req->toArray();
    expect($data['AD.CUST_BOR_ORDER_ID'])->toBe('000001');
    expect($data['ADDENDUM'])->toBe('AD,TD');
});

test('payments()->purchase AD.CUST_BOR_ORDER_ID strips semicolons and truncates to 22 chars', function () {
    $req = $this->client->payments()->purchase(
        amount: '10.00',
        order: '000001',
        description: 'Test',
        mInfo: ['cardholderName' => 'John Doe', 'email' => 'john@example.com'],
        adCustBorOrderId: 'ORDER;WITH;SEMICOLONS;AND;VERY;LONG;VALUE',
    );

    $data = $req->toArray();
    expect($data['AD.CUST_BOR_ORDER_ID'])->toBe('ORDERWITHSEMICOLONSAND')
        ->toHaveLength(22)
        ->not->toContain(';');
});

test('payments()->purchase rejects mInfo without cardholderName', function () {
    $this->client->payments()->purchase(
        amount: '10.00',
        order: '000001',
        description: 'Test',
        mInfo: ['email' => 'john@example.com'],
    );
})->throws(ConfigurationException::class, 'M_INFO must contain a non-empty "cardholderName"');

test('payments()->purchase rejects mInfo without email or phone', function () {
    $this->client->payments()->purchase(
        amount: '10.00',
        order: '000001',
        description: 'Test',
        mInfo: ['cardholderName' => 'John Doe'],
    );
})->throws(ConfigurationException::class, 'M_INFO must contain "email" and/or "mobilePhone"');

test('payments()->purchase rejects mInfo with cardholderName exceeding 45 chars', function () {
    $this->client->payments()->purchase(
        amount: '10.00',
        order: '000001',
        description: 'Test',
        mInfo: ['cardholderName' => str_repeat('A', 46), 'email' => 'john@example.com'],
    );
})->throws(ConfigurationException::class, 'M_INFO "cardholderName" must not exceed 45 characters');

test('payments()->purchase accepts mInfo with phone only', function () {
    $req = $this->client->payments()->purchase(
        amount: '10.00',
        order: '000001',
        description: 'Test',
        mInfo: ['cardholderName' => 'John Doe', 'mobilePhone' => ['cc' => '359', 'subscriber' => '888123456']],
    );

    expect($req->toArray())->toHaveKey('M_INFO');
});

// ---------------------------------------------------------------------------
// payments()->reverse
// ---------------------------------------------------------------------------

test('payments()->reverse returns ReversalRequest with correct fields', function () {
    $timestamp = gmdate('YmdHis');
    $req = $this->client->payments()->reverse(
        amount: '9.00',
        order: '000001',
        rrn: '012345678901',
        intRef: 'ABCDEF123456',
        description: 'Test reversal',
        timestamp: $timestamp,
        nonce: 'AABBCCDDAABBCCDDAABBCCDDAABBCCDD',
    );

    expect($req)->toBeInstanceOf(ReversalRequest::class);

    $data = $req->toArray();

    expect($data['TRTYPE'])->toBe('24');
    expect($data['RRN'])->toBe('012345678901');
    expect($data['INT_REF'])->toBe('ABCDEF123456');
    expect($data)->toHaveKey('P_SIGN');
    expect($data['P_SIGN'])->toHaveLength(512)->toMatch('/^[A-F0-9]{512}$/');
});

// ---------------------------------------------------------------------------
// status()->check
// ---------------------------------------------------------------------------

test('status()->check returns StatusCheckRequest with correct fields', function () {
    $req = $this->client->status()->check(
        order: '000001',
        transactionType: TransactionType::Purchase,
        nonce: 'AABBCCDDAABBCCDDAABBCCDDAABBCCDD',
    );

    expect($req)->toBeInstanceOf(StatusCheckRequest::class);

    $data = $req->toArray();

    expect($data['TRTYPE'])->toBe('90');
    expect($data['TRAN_TRTYPE'])->toBe('1');
    expect($data)->toHaveKey('P_SIGN');
    expect($data['P_SIGN'])->toHaveLength(512)->toMatch('/^[A-F0-9]{512}$/');
});

// ---------------------------------------------------------------------------
// preAuth()->create
// ---------------------------------------------------------------------------

test('preAuth()->create returns PreAuthRequest with correct fields', function () {
    $req = $this->client->preAuth()->create(
        amount: '50.00',
        order: '000002',
        description: 'Pre-auth test',
        mInfo: ['cardholderName' => 'John Doe', 'email' => 'john@example.com'],
    );

    expect($req)->toBeInstanceOf(PreAuthRequest::class);

    $data = $req->toArray();

    expect($data['TRTYPE'])->toBe('12');
    expect($data['AMOUNT'])->toBe('50.00');
    expect($data['CURRENCY'])->toBe('EUR');
    expect($data['P_SIGN'])->toHaveLength(512)->toMatch('/^[A-F0-9]{512}$/');
    expect($data)->toHaveKey('M_INFO');
    expect($data['ADDENDUM'])->toBe('AD,TD');
});

// ---------------------------------------------------------------------------
// preAuth()->complete
// ---------------------------------------------------------------------------

test('preAuth()->complete returns PreAuthCompleteRequest with correct fields', function () {
    $req = $this->client->preAuth()->complete(
        amount: '50.00',
        order: '000002',
        rrn: '012345678901',
        intRef: 'ABCDEF123456',
        description: 'Complete pre-auth',
    );

    expect($req)->toBeInstanceOf(PreAuthCompleteRequest::class);

    $data = $req->toArray();

    expect($data['TRTYPE'])->toBe('21');
    expect($data['AMOUNT'])->toBe('50.00');
    expect($data['RRN'])->toBe('012345678901');
    expect($data['INT_REF'])->toBe('ABCDEF123456');
    expect($data['P_SIGN'])->toHaveLength(512)->toMatch('/^[A-F0-9]{512}$/');
    expect($data['ADDENDUM'])->toBe('AD,TD');
});

// ---------------------------------------------------------------------------
// preAuth()->reverse
// ---------------------------------------------------------------------------

test('preAuth()->reverse returns PreAuthReversalRequest with correct fields', function () {
    $req = $this->client->preAuth()->reverse(
        amount: '50.00',
        order: '000002',
        rrn: '012345678901',
        intRef: 'ABCDEF123456',
        description: 'Reverse pre-auth',
    );

    expect($req)->toBeInstanceOf(PreAuthReversalRequest::class);

    $data = $req->toArray();

    expect($data['TRTYPE'])->toBe('22');
    expect($data['AMOUNT'])->toBe('50.00');
    expect($data['RRN'])->toBe('012345678901');
    expect($data['INT_REF'])->toBe('ABCDEF123456');
    expect($data['P_SIGN'])->toHaveLength(512)->toMatch('/^[A-F0-9]{512}$/');
    expect($data['ADDENDUM'])->toBe('AD,TD');
});

// ---------------------------------------------------------------------------
// responses()->parse — full round-trip
// ---------------------------------------------------------------------------

test('responses()->parse full round-trip: purchase request signed mock response', function () {
    $privateKey = file_get_contents(__DIR__ . '/../fixtures/test_private_key.pem');
    $publicKey = file_get_contents(__DIR__ . '/../fixtures/test_public_key.pem');

    $timestamp = gmdate('YmdHis');
    $nonce = 'AABBCCDDAABBCCDDAABBCCDDAABBCCDD';

    $req = $this->client->payments()->purchase(
        amount: '9.00',
        order: '000001',
        description: 'Test payment',
        mInfo: ['cardholderName' => 'John Doe', 'email' => 'john@example.com'],
        timestamp: $timestamp,
        nonce: $nonce,
    );

    $requestData = $req->toArray();

    $responseFields = [
        'ACTION'       => '0',
        'RC'           => '00',
        'APPROVAL'     => '123456',
        'TERMINAL'     => $requestData['TERMINAL'],
        'TRTYPE'       => $requestData['TRTYPE'],
        'AMOUNT'       => $requestData['AMOUNT'],
        'CURRENCY'     => $requestData['CURRENCY'],
        'ORDER'        => $requestData['ORDER'],
        'RRN'          => '012345678901',
        'INT_REF'      => 'ABCDEF123456',
        'PARES_STATUS' => 'Y',
        'ECI'          => '05',
        'TIMESTAMP'    => $requestData['TIMESTAMP'],
        'NONCE'        => $requestData['NONCE'],
    ];

    $macGeneral = new MacGeneral();
    $signer = new Signer();
    $signingData = $macGeneral->buildResponseSigningData($responseFields);
    $responseFields['P_SIGN'] = $signer->sign($signingData, $privateKey);

    $response = $this->client->responses()->parse(
        $responseFields,
        TransactionType::Purchase,
        publicKey: $publicKey,
    );

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->isSuccessful())->toBeTrue();
    expect($response->getOrder())->toBe('000001');
    expect($response->getApproval())->toBe('123456');
});

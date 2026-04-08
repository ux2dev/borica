<?php

declare(strict_types=1);

use Ux2Dev\Borica\Enum\TransactionType;
use Ux2Dev\Borica\Exception\InvalidResponseException;
use Ux2Dev\Borica\Response\Response;
use Ux2Dev\Borica\Response\ResponseParser;
use Ux2Dev\Borica\Signing\MacGeneral;
use Ux2Dev\Borica\Signing\Signer;

beforeEach(function () {
    $this->macGeneral = new MacGeneral();
    $this->signer = new Signer();
    $this->privateKey = file_get_contents(__DIR__ . '/../fixtures/test_private_key.pem');
    $this->publicKey = file_get_contents(__DIR__ . '/../fixtures/test_public_key.pem');
    $this->parser = new ResponseParser($this->macGeneral, $this->signer, $this->publicKey);

    $this->responseData = [
        'ACTION' => '0',
        'RC' => '00',
        'APPROVAL' => '123456',
        'TERMINAL' => 'V1800001',
        'TRTYPE' => '1',
        'AMOUNT' => '9.00',
        'CURRENCY' => 'EUR',
        'ORDER' => '000001',
        'RRN' => '012345678901',
        'INT_REF' => 'ABCDEF123456',
        'PARES_STATUS' => 'Y',
        'ECI' => '05',
        'TIMESTAMP' => gmdate('YmdHis'),
        'NONCE' => 'AABBCCDD',
    ];
});

function signResponse(object $context): void
{
    $signingData = $context->macGeneral->buildResponseSigningData($context->responseData);
    $pSign = $context->signer->sign($signingData, $context->privateKey);
    $context->responseData['P_SIGN'] = $pSign;
}

test('parses valid signed response and returns Response', function () {
    signResponse($this);

    $response = $this->parser->parse($this->responseData, TransactionType::Purchase);

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->isSuccessful())->toBeTrue();
    expect($response->getOrder())->toBe('000001');
    expect($response->getApproval())->toBe('123456');
});

test('throws InvalidResponseException on tampered P_SIGN', function () {
    signResponse($this);
    $pSign = $this->responseData['P_SIGN'];
    $this->responseData['P_SIGN'] = ($pSign[0] === 'A' ? 'B' : 'A') . substr($pSign, 1);

    $this->parser->parse($this->responseData, TransactionType::Purchase);
})->throws(InvalidResponseException::class, 'P_SIGN verification failed');

test('throws InvalidResponseException on missing P_SIGN', function () {
    $this->parser->parse($this->responseData, TransactionType::Purchase);
})->throws(InvalidResponseException::class, 'Missing P_SIGN in response');

test('InvalidResponseException redacts sensitive fields and exposes getResponseData', function () {
    try {
        $this->parser->parse($this->responseData, TransactionType::Purchase);
    } catch (InvalidResponseException $e) {
        $data = $e->getResponseData();

        expect($data['APPROVAL'])->toBe('[REDACTED]');
        expect($data['CARD'] ?? null)->toBeNull();
        expect($data['RRN'])->toBe('[REDACTED]');
        expect($data['INT_REF'])->toBe('[REDACTED]');
        expect($data['TERMINAL'])->toBe('V1800001');
        expect($data['ORDER'])->toBe('000001');
        return;
    }

    $this->fail('Expected InvalidResponseException was not thrown');
});

test('StatusCheck response with empty CURRENCY substitutes USD for verification', function () {
    // Simulate BORICA behavior: sign with CURRENCY=USD but send empty in POST
    $statusCheckData = [
        'ACTION' => '0',
        'RC' => '00',
        'APPROVAL' => '',
        'TERMINAL' => 'V1800001',
        'TRTYPE' => '90',
        'AMOUNT' => '9.00',
        'CURRENCY' => 'USD',
        'ORDER' => '000001',
        'RRN' => '',
        'INT_REF' => '',
        'PARES_STATUS' => '',
        'ECI' => '',
        'TIMESTAMP' => gmdate('YmdHis'),
        'NONCE' => 'AABBCCDD',
    ];

    // Sign with CURRENCY=USD
    $signingData = $this->macGeneral->buildResponseSigningData($statusCheckData);
    $pSign = $this->signer->sign($signingData, $this->privateKey);

    // But present with empty CURRENCY (as BORICA actually sends)
    $statusCheckData['CURRENCY'] = '';
    $statusCheckData['P_SIGN'] = $pSign;

    $response = $this->parser->parse($statusCheckData, TransactionType::StatusCheck);

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->isSuccessful())->toBeTrue();
});

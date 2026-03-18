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
        'CURRENCY' => 'BGN',
        'ORDER' => '000001',
        'RRN' => '012345678901',
        'INT_REF' => 'ABCDEF123456',
        'PARES_STATUS' => 'Y',
        'ECI' => '05',
        'TIMESTAMP' => '20201012124757',
        'NONCE' => 'AABBCCDD',
    ];
});

test('parses valid signed response and returns Response', function () {
    $signingData = $this->macGeneral->buildResponseSigningData(TransactionType::Purchase, $this->responseData);
    $pSign = $this->signer->sign($signingData, $this->privateKey);
    $this->responseData['P_SIGN'] = $pSign;

    $response = $this->parser->parse($this->responseData, TransactionType::Purchase);

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->isSuccessful())->toBeTrue();
    expect($response->getOrder())->toBe('000001');
    expect($response->getApproval())->toBe('123456');
});

test('throws InvalidResponseException on tampered P_SIGN', function () {
    $signingData = $this->macGeneral->buildResponseSigningData(TransactionType::Purchase, $this->responseData);
    $pSign = $this->signer->sign($signingData, $this->privateKey);
    // Tamper: flip first char
    $tamperedPSign = ($pSign[0] === 'A' ? 'B' : 'A') . substr($pSign, 1);
    $this->responseData['P_SIGN'] = $tamperedPSign;

    $this->parser->parse($this->responseData, TransactionType::Purchase);
})->throws(InvalidResponseException::class, 'P_SIGN verification failed');

test('throws InvalidResponseException on missing P_SIGN', function () {
    $this->parser->parse($this->responseData, TransactionType::Purchase);
})->throws(InvalidResponseException::class, 'Missing P_SIGN in response');

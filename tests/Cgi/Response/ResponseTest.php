<?php

declare(strict_types=1);

use Ux2Dev\Borica\Cgi\Response\Response;

test('isSuccessful returns true when ACTION is 0 and RC is 00', function () {
    $response = new Response([
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
        'CARD' => '400000****0002',
        'CARD_BRAND' => 'VISA',
        'ECI' => '05',
        'PARES_STATUS' => 'Y',
        'TIMESTAMP' => '20201012124757',
        'NONCE' => 'AABBCCDD',
        'STATUSMSG' => 'Transaction successful',
        'AUTH_STEP_RES' => 'AUTHENTICATED',
        'CARDHOLDERINFO' => 'John Doe',
    ]);

    expect($response->isSuccessful())->toBeTrue();
    expect($response->getAction())->toBe('0');
    expect($response->getRc())->toBe('00');
    expect($response->getApproval())->toBe('123456');
    expect($response->getTerminal())->toBe('V1800001');
    expect($response->getTrtype())->toBe('1');
    expect($response->getAmount())->toBe('9.00');
    expect($response->getCurrency())->toBe('EUR');
    expect($response->getOrder())->toBe('000001');
    expect($response->getRrn())->toBe('012345678901');
    expect($response->getIntRef())->toBe('ABCDEF123456');
    expect($response->getCard())->toBe('400000****0002');
    expect($response->getCardBrand())->toBe('VISA');
    expect($response->getEci())->toBe('05');
    expect($response->getParesStatus())->toBe('Y');
    expect($response->getTimestamp())->toBe('20201012124757');
    expect($response->getNonce())->toBe('AABBCCDD');
    expect($response->getStatusMessage())->toBe('Transaction successful');
    expect($response->getAuthStepResult())->toBe('AUTHENTICATED');
    expect($response->getCardholderInfo())->toBe('John Doe');
    expect($response->getErrorMessage())->toBe('');
});

test('isSuccessful returns false when ACTION is 2 and RC is 05', function () {
    $response = new Response([
        'ACTION' => '2',
        'RC' => '05',
        'TERMINAL' => 'V1800001',
        'TRTYPE' => '1',
        'ORDER' => '000001',
        'TIMESTAMP' => '20201012124757',
        'NONCE' => 'AABBCCDD',
    ]);

    expect($response->isSuccessful())->toBeFalse();
    expect($response->getErrorMessage())->toContain('Do not honour');
});

test('getErrorMessage returns gateway error for negative RC codes', function () {
    $response = new Response([
        'ACTION' => '2',
        'RC' => '-17',
        'TERMINAL' => 'V1800001',
        'TRTYPE' => '1',
        'ORDER' => '000001',
        'TIMESTAMP' => '20201012124757',
        'NONCE' => 'AABBCCDD',
    ]);

    expect($response->getErrorMessage())->toContain('denied');
});

test('missing optional fields return null', function () {
    $response = new Response([
        'ACTION' => '0',
        'RC' => '00',
        'TERMINAL' => 'V1800001',
        'TRTYPE' => '1',
        'ORDER' => '000001',
        'TIMESTAMP' => '20201012124757',
        'NONCE' => 'AABBCCDD',
    ]);

    expect($response->getCard())->toBeNull();
    expect($response->getCardBrand())->toBeNull();
    expect($response->getApproval())->toBeNull();
    expect($response->getCardholderInfo())->toBeNull();
    expect($response->getAuthStepResult())->toBeNull();
    expect($response->getStatusMessage())->toBeNull();
    expect($response->getRrn())->toBeNull();
    expect($response->getIntRef())->toBeNull();
    expect($response->getAmount())->toBeNull();
    expect($response->getCurrency())->toBeNull();
    expect($response->getEci())->toBeNull();
    expect($response->getParesStatus())->toBeNull();
});

test('empty string optional fields return null', function () {
    $response = new Response([
        'ACTION' => '0',
        'RC' => '00',
        'TERMINAL' => 'V1800001',
        'TRTYPE' => '1',
        'ORDER' => '000001',
        'TIMESTAMP' => '20201012124757',
        'NONCE' => 'AABBCCDD',
        'CARD' => '',
        'APPROVAL' => '',
    ]);

    expect($response->getCard())->toBeNull();
    expect($response->getApproval())->toBeNull();
});

test('toSafeArray redacts all sensitive fields', function () {
    $response = new Response([
        'ACTION' => '0',
        'RC' => '00',
        'TERMINAL' => 'V1800001',
        'ORDER' => '000001',
        'CARD' => '400000****0002',
        'APPROVAL' => '123456',
        'P_SIGN' => 'ABCDEF1234',
        'RRN' => '012345678901',
        'INT_REF' => 'ABCDEF123456',
        'CARDHOLDERINFO' => 'John Doe',
    ]);

    $safe = $response->toSafeArray();

    expect($safe['CARD'])->toBe('[REDACTED]');
    expect($safe['APPROVAL'])->toBe('[REDACTED]');
    expect($safe['P_SIGN'])->toBe('[REDACTED]');
    expect($safe['RRN'])->toBe('[REDACTED]');
    expect($safe['INT_REF'])->toBe('[REDACTED]');
    expect($safe['CARDHOLDERINFO'])->toBe('[REDACTED]');
    expect($safe['ORDER'])->toBe('000001');
    expect($safe['TERMINAL'])->toBe('V1800001');
});

test('debugInfo redacts sensitive fields', function () {
    $response = new Response([
        'ACTION' => '0',
        'CARD' => '400000****0002',
        'P_SIGN' => 'ABCDEF1234',
    ]);

    $debug = (array) $response->__debugInfo();

    expect($debug['CARD'])->toBe('[REDACTED]');
    expect($debug['P_SIGN'])->toBe('[REDACTED]');
});

test('isSoftDecline returns true for ACTION 21 and RC 1A', function () {
    $response = new Response([
        'ACTION' => '21',
        'RC' => '1A',
        'TERMINAL' => 'V1800001',
        'TRTYPE' => '1',
        'ORDER' => '000001',
        'TIMESTAMP' => '20201012124757',
        'NONCE' => 'AABBCCDD',
    ]);

    expect($response->isSoftDecline())->toBeTrue();
    expect($response->isSuccessful())->toBeFalse();
});

test('isSoftDecline returns false for successful response', function () {
    $response = new Response([
        'ACTION' => '0',
        'RC' => '00',
        'TERMINAL' => 'V1800001',
        'ORDER' => '000001',
    ]);

    expect($response->isSoftDecline())->toBeFalse();
});

test('getTranDate and getTranTrtype return values for status check response', function () {
    $response = new Response([
        'ACTION' => '0',
        'RC' => '00',
        'TERMINAL' => 'V1800001',
        'TRTYPE' => '90',
        'ORDER' => '000001',
        'TIMESTAMP' => '20201012124757',
        'NONCE' => 'AABBCCDD',
        'TRAN_DATE' => '20201012170707',
        'TRAN_TRTYPE' => '1',
        'LANG' => 'BG',
    ]);

    expect($response->getTranDate())->toBe('20201012170707');
    expect($response->getTranTrtype())->toBe('1');
    expect($response->getLang())->toBe('BG');
});

test('getTranDate and getTranTrtype return null when not present', function () {
    $response = new Response([
        'ACTION' => '0',
        'RC' => '00',
        'TERMINAL' => 'V1800001',
        'ORDER' => '000001',
    ]);

    expect($response->getTranDate())->toBeNull();
    expect($response->getTranTrtype())->toBeNull();
    expect($response->getLang())->toBeNull();
});

test('__serialize redacts sensitive fields', function () {
    $response = new Response([
        'ACTION' => '0',
        'RC' => '00',
        'CARD' => '400000****0002',
        'P_SIGN' => 'ABCDEF1234',
    ]);

    $serialized = $response->__serialize();

    expect($serialized['CARD'])->toBe('[REDACTED]');
    expect($serialized['P_SIGN'])->toBe('[REDACTED]');
});

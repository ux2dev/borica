<?php

declare(strict_types=1);

use Ux2Dev\Borica\Response\Response;

test('isSuccessful returns true when ACTION is 0 and RC is 00', function () {
    $response = new Response([
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
    expect($response->getCurrency())->toBe('BGN');
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

<?php
declare(strict_types=1);

use Ux2Dev\Borica\Enum\TransactionType;
use Ux2Dev\Borica\Request\StatusCheckRequest;

test('getTransactionType returns StatusCheck', function () {
    $request = new StatusCheckRequest(
        terminal: 'V1800001',
        order: '000006',
        nonce: 'AABBCCDD',
        pSign: 'ABCDEF',
        tranTrtype: '1',
    );

    expect($request->getTransactionType())->toBe(TransactionType::StatusCheck);
});

test('toArray includes all required fields with TRTYPE 90', function () {
    $request = new StatusCheckRequest(
        terminal: 'V1800001',
        order: '000006',
        nonce: 'AABBCCDD',
        pSign: 'ABCDEF',
        tranTrtype: '1',
    );

    $data = $request->toArray();

    expect($data)->toHaveKeys(['TERMINAL', 'TRTYPE', 'ORDER', 'TRAN_TRTYPE', 'NONCE', 'P_SIGN']);
    expect($data['TERMINAL'])->toBe('V1800001');
    expect($data['TRTYPE'])->toBe('90');
    expect($data['ORDER'])->toBe('000006');
    expect($data['TRAN_TRTYPE'])->toBe('1');
    expect($data['NONCE'])->toBe('AABBCCDD');
    expect($data['P_SIGN'])->toBe('ABCDEF');
});

test('toArray does not include AMOUNT or CURRENCY', function () {
    $request = new StatusCheckRequest(
        terminal: 'V1800001',
        order: '000006',
        nonce: 'AABBCCDD',
        pSign: 'ABCDEF',
        tranTrtype: '1',
    );

    $data = $request->toArray();

    expect($data)->not->toHaveKey('AMOUNT');
    expect($data)->not->toHaveKey('CURRENCY');
});

test('getSigningFields returns TERMINAL TRTYPE ORDER NONCE only', function () {
    $request = new StatusCheckRequest(
        terminal: 'V1800001',
        order: '000006',
        nonce: 'AABBCCDD',
        pSign: 'ABCDEF',
        tranTrtype: '1',
    );

    $fields = $request->getSigningFields();

    expect(array_keys($fields))->toBe(['TERMINAL', 'TRTYPE', 'ORDER', 'NONCE']);
    expect($fields['TRTYPE'])->toBe('90');
    expect($fields['TERMINAL'])->toBe('V1800001');
    expect($fields['ORDER'])->toBe('000006');
    expect($fields['NONCE'])->toBe('AABBCCDD');
});

test('getSigningFields does not include TRAN_TRTYPE or P_SIGN', function () {
    $request = new StatusCheckRequest(
        terminal: 'V1800001',
        order: '000006',
        nonce: 'AABBCCDD',
        pSign: 'ABCDEF',
        tranTrtype: '1',
    );

    $fields = $request->getSigningFields();

    expect($fields)->not->toHaveKey('TRAN_TRTYPE');
    expect($fields)->not->toHaveKey('P_SIGN');
});

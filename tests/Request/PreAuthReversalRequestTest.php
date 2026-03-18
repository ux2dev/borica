<?php
declare(strict_types=1);

use Ux2Dev\Borica\Enum\TransactionType;
use Ux2Dev\Borica\Request\PreAuthReversalRequest;

test('getTransactionType returns PreAuthReversal', function () {
    $request = new PreAuthReversalRequest(
        terminal: 'V1800001',
        amount: '100.50',
        currency: 'EUR',
        order: '000004',
        timestamp: '20201012124757',
        nonce: 'AABBCCDD',
        pSign: 'ABCDEF',
        merchant: 'MERCHANT001',
        merchantName: 'Test Shop',
        description: 'Pre-auth reversal',
        rrn: '123456789012',
        intRef: '1234567890ABCDEF',
    );

    expect($request->getTransactionType())->toBe(TransactionType::PreAuthReversal);
});

test('toArray includes all mandatory fields including RRN and INT_REF', function () {
    $request = new PreAuthReversalRequest(
        terminal: 'V1800001',
        amount: '100.50',
        currency: 'EUR',
        order: '000004',
        timestamp: '20201012124757',
        nonce: 'AABBCCDD',
        pSign: 'ABCDEF',
        merchant: 'MERCHANT001',
        merchantName: 'Test Shop',
        description: 'Pre-auth reversal',
        rrn: '123456789012',
        intRef: '1234567890ABCDEF',
    );

    $data = $request->toArray();

    expect($data)->toHaveKeys(['TERMINAL', 'TRTYPE', 'AMOUNT', 'CURRENCY', 'ORDER', 'DESC', 'MERCHANT', 'MERCH_NAME', 'RRN', 'INT_REF', 'TIMESTAMP', 'NONCE', 'P_SIGN', 'LANG']);
    expect($data['TERMINAL'])->toBe('V1800001');
    expect($data['TRTYPE'])->toBe('22');
    expect($data['AMOUNT'])->toBe('100.50');
    expect($data['CURRENCY'])->toBe('EUR');
    expect($data['ORDER'])->toBe('000004');
    expect($data['DESC'])->toBe('Pre-auth reversal');
    expect($data['MERCHANT'])->toBe('MERCHANT001');
    expect($data['MERCH_NAME'])->toBe('Test Shop');
    expect($data['RRN'])->toBe('123456789012');
    expect($data['INT_REF'])->toBe('1234567890ABCDEF');
    expect($data['TIMESTAMP'])->toBe('20201012124757');
    expect($data['NONCE'])->toBe('AABBCCDD');
    expect($data['P_SIGN'])->toBe('ABCDEF');
    expect($data['LANG'])->toBe('BG');
});

test('getSigningFields does not include RRN and INT_REF', function () {
    $request = new PreAuthReversalRequest(
        terminal: 'V1800001',
        amount: '100.50',
        currency: 'EUR',
        order: '000004',
        timestamp: '20201012124757',
        nonce: 'AABBCCDD',
        pSign: 'ABCDEF',
        merchant: 'MERCHANT001',
        merchantName: 'Test Shop',
        description: 'Pre-auth reversal',
        rrn: '123456789012',
        intRef: '1234567890ABCDEF',
    );

    $fields = $request->getSigningFields();

    expect(array_keys($fields))->toBe(['TERMINAL', 'TRTYPE', 'AMOUNT', 'CURRENCY', 'ORDER', 'TIMESTAMP', 'NONCE']);
    expect($fields)->not->toHaveKey('RRN');
    expect($fields)->not->toHaveKey('INT_REF');
    expect($fields['TRTYPE'])->toBe('22');
});

test('optional fields appear in toArray only when non-empty', function () {
    $request = new PreAuthReversalRequest(
        terminal: 'V1800001',
        amount: '100.50',
        currency: 'EUR',
        order: '000004',
        timestamp: '20201012124757',
        nonce: 'AABBCCDD',
        pSign: 'ABCDEF',
        merchant: 'MERCHANT001',
        merchantName: 'Test Shop',
        description: 'Pre-auth reversal',
        rrn: '123456789012',
        intRef: '1234567890ABCDEF',
    );

    $data = $request->toArray();

    expect($data)->not->toHaveKey('AD.CUST_BOR_ORDER_ID');
    expect($data)->not->toHaveKey('ADDENDUM');
    expect($data)->not->toHaveKey('COUNTRY');
    expect($data)->not->toHaveKey('MERCH_GMT');
    expect($data)->not->toHaveKey('EMAIL');
    expect($data)->not->toHaveKey('MERCH_URL');
});

test('optional fields appear in toArray when provided', function () {
    $request = new PreAuthReversalRequest(
        terminal: 'V1800001',
        amount: '100.50',
        currency: 'EUR',
        order: '000004',
        timestamp: '20201012124757',
        nonce: 'AABBCCDD',
        pSign: 'ABCDEF',
        merchant: 'MERCHANT001',
        merchantName: 'Test Shop',
        description: 'Pre-auth reversal',
        rrn: '123456789012',
        intRef: '1234567890ABCDEF',
        adCustBorOrderId: 'CUSTORDER4',
        country: 'BG',
        merchGmt: '+02:00',
        addendum: 'AD,TD',
        email: 'test@example.com',
        merchantUrl: 'https://example.com',
        language: 'EN',
    );

    $data = $request->toArray();

    expect($data['AD.CUST_BOR_ORDER_ID'])->toBe('CUSTORDER4');
    expect($data['COUNTRY'])->toBe('BG');
    expect($data['MERCH_GMT'])->toBe('+02:00');
    expect($data['ADDENDUM'])->toBe('AD,TD');
    expect($data['EMAIL'])->toBe('test@example.com');
    expect($data['MERCH_URL'])->toBe('https://example.com');
    expect($data['LANG'])->toBe('EN');
});

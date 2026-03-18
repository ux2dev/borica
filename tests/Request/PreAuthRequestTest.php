<?php
declare(strict_types=1);

use Ux2Dev\Borica\Enum\TransactionType;
use Ux2Dev\Borica\Request\PreAuthRequest;

test('getTransactionType returns PreAuth', function () {
    $request = new PreAuthRequest(
        terminal: 'V1800001',
        trtype: '12',
        amount: '100.50',
        currency: 'EUR',
        order: '000002',
        timestamp: '20201012124757',
        nonce: 'AABBCCDD',
        pSign: 'ABCDEF',
        merchant: 'MERCHANT001',
        merchantName: 'Test Shop',
        description: 'Pre-auth payment',
    );

    expect($request->getTransactionType())->toBe(TransactionType::PreAuth);
});

test('toArray includes all mandatory fields', function () {
    $request = new PreAuthRequest(
        terminal: 'V1800001',
        trtype: '12',
        amount: '100.50',
        currency: 'EUR',
        order: '000002',
        timestamp: '20201012124757',
        nonce: 'AABBCCDD',
        pSign: 'ABCDEF',
        merchant: 'MERCHANT001',
        merchantName: 'Test Shop',
        description: 'Pre-auth payment',
    );

    $data = $request->toArray();

    expect($data)->toHaveKeys(['TERMINAL', 'TRTYPE', 'AMOUNT', 'CURRENCY', 'ORDER', 'DESC', 'MERCHANT', 'MERCH_NAME', 'TIMESTAMP', 'NONCE', 'P_SIGN', 'LANG']);
    expect($data['TERMINAL'])->toBe('V1800001');
    expect($data['TRTYPE'])->toBe('12');
    expect($data['AMOUNT'])->toBe('100.50');
    expect($data['CURRENCY'])->toBe('EUR');
    expect($data['ORDER'])->toBe('000002');
    expect($data['DESC'])->toBe('Pre-auth payment');
    expect($data['MERCHANT'])->toBe('MERCHANT001');
    expect($data['MERCH_NAME'])->toBe('Test Shop');
    expect($data['TIMESTAMP'])->toBe('20201012124757');
    expect($data['NONCE'])->toBe('AABBCCDD');
    expect($data['P_SIGN'])->toBe('ABCDEF');
    expect($data['LANG'])->toBe('BG');
});

test('getSigningFields returns only MAC signing fields', function () {
    $request = new PreAuthRequest(
        terminal: 'V1800001',
        trtype: '12',
        amount: '100.50',
        currency: 'EUR',
        order: '000002',
        timestamp: '20201012124757',
        nonce: 'AABBCCDD',
        pSign: 'ABCDEF',
        merchant: 'MERCHANT001',
        merchantName: 'Test Shop',
        description: 'Pre-auth payment',
    );

    $fields = $request->getSigningFields();

    expect(array_keys($fields))->toBe(['TERMINAL', 'TRTYPE', 'AMOUNT', 'CURRENCY', 'ORDER', 'TIMESTAMP', 'NONCE']);
    expect($fields)->not->toHaveKey('P_SIGN');
    expect($fields)->not->toHaveKey('DESC');
    expect($fields)->not->toHaveKey('MERCHANT');
});

test('optional fields appear in toArray only when non-empty', function () {
    $request = new PreAuthRequest(
        terminal: 'V1800001',
        trtype: '12',
        amount: '100.50',
        currency: 'EUR',
        order: '000002',
        timestamp: '20201012124757',
        nonce: 'AABBCCDD',
        pSign: 'ABCDEF',
        merchant: 'MERCHANT001',
        merchantName: 'Test Shop',
        description: 'Pre-auth payment',
    );

    $data = $request->toArray();

    expect($data)->not->toHaveKey('AD.CUST_BOR_ORDER_ID');
    expect($data)->not->toHaveKey('ADDENDUM');
    expect($data)->not->toHaveKey('COUNTRY');
    expect($data)->not->toHaveKey('MERCH_GMT');
    expect($data)->not->toHaveKey('EMAIL');
    expect($data)->not->toHaveKey('MERCH_URL');
    expect($data)->not->toHaveKey('M_INFO');
});

test('optional fields appear in toArray when provided', function () {
    $request = new PreAuthRequest(
        terminal: 'V1800001',
        trtype: '12',
        amount: '100.50',
        currency: 'EUR',
        order: '000002',
        timestamp: '20201012124757',
        nonce: 'AABBCCDD',
        pSign: 'ABCDEF',
        merchant: 'MERCHANT001',
        merchantName: 'Test Shop',
        description: 'Pre-auth payment',
        adCustBorOrderId: 'CUSTORDER2',
        country: 'BG',
        merchGmt: '+02:00',
        addendum: 'AD,TD',
        email: 'test@example.com',
        merchantUrl: 'https://example.com',
        language: 'EN',
        mInfo: 'base64data',
    );

    $data = $request->toArray();

    expect($data['AD.CUST_BOR_ORDER_ID'])->toBe('CUSTORDER2');
    expect($data['COUNTRY'])->toBe('BG');
    expect($data['MERCH_GMT'])->toBe('+02:00');
    expect($data['ADDENDUM'])->toBe('AD,TD');
    expect($data['EMAIL'])->toBe('test@example.com');
    expect($data['MERCH_URL'])->toBe('https://example.com');
    expect($data['LANG'])->toBe('EN');
    expect($data['M_INFO'])->toBe('base64data');
});

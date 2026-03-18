<?php
declare(strict_types=1);

use Ux2Dev\Borica\Enum\TransactionType;
use Ux2Dev\Borica\Request\PaymentRequest;

test('getTransactionType returns Purchase', function () {
    $request = new PaymentRequest(
        terminal: 'V1800001',
        trtype: '1',
        amount: '9.00',
        currency: 'BGN',
        order: '000001',
        timestamp: '20201012124757',
        nonce: 'AABBCCDD',
        pSign: 'ABCDEF',
        merchant: 'MERCHANT001',
        merchantName: 'Test Shop',
        description: 'Test payment',
    );

    expect($request->getTransactionType())->toBe(TransactionType::Purchase);
});

test('toArray includes all mandatory fields', function () {
    $request = new PaymentRequest(
        terminal: 'V1800001',
        trtype: '1',
        amount: '9.00',
        currency: 'BGN',
        order: '000001',
        timestamp: '20201012124757',
        nonce: 'AABBCCDD',
        pSign: 'ABCDEF',
        merchant: 'MERCHANT001',
        merchantName: 'Test Shop',
        description: 'Test payment',
    );

    $data = $request->toArray();

    expect($data)->toHaveKeys(['TERMINAL', 'TRTYPE', 'AMOUNT', 'CURRENCY', 'ORDER', 'DESC', 'MERCHANT', 'MERCH_NAME', 'TIMESTAMP', 'NONCE', 'P_SIGN', 'LANG']);
    expect($data['TERMINAL'])->toBe('V1800001');
    expect($data['TRTYPE'])->toBe('1');
    expect($data['AMOUNT'])->toBe('9.00');
    expect($data['CURRENCY'])->toBe('BGN');
    expect($data['ORDER'])->toBe('000001');
    expect($data['DESC'])->toBe('Test payment');
    expect($data['MERCHANT'])->toBe('MERCHANT001');
    expect($data['MERCH_NAME'])->toBe('Test Shop');
    expect($data['TIMESTAMP'])->toBe('20201012124757');
    expect($data['NONCE'])->toBe('AABBCCDD');
    expect($data['P_SIGN'])->toBe('ABCDEF');
    expect($data['LANG'])->toBe('BG');
});

test('getSigningFields returns only MAC signing fields', function () {
    $request = new PaymentRequest(
        terminal: 'V1800001',
        trtype: '1',
        amount: '9.00',
        currency: 'BGN',
        order: '000001',
        timestamp: '20201012124757',
        nonce: 'AABBCCDD',
        pSign: 'ABCDEF',
        merchant: 'MERCHANT001',
        merchantName: 'Test Shop',
        description: 'Test payment',
    );

    $fields = $request->getSigningFields();

    expect(array_keys($fields))->toBe(['TERMINAL', 'TRTYPE', 'AMOUNT', 'CURRENCY', 'ORDER', 'TIMESTAMP', 'NONCE']);
    expect($fields)->not->toHaveKey('P_SIGN');
    expect($fields)->not->toHaveKey('DESC');
    expect($fields)->not->toHaveKey('MERCHANT');
});

test('optional fields appear in toArray only when non-empty', function () {
    $request = new PaymentRequest(
        terminal: 'V1800001',
        trtype: '1',
        amount: '9.00',
        currency: 'BGN',
        order: '000001',
        timestamp: '20201012124757',
        nonce: 'AABBCCDD',
        pSign: 'ABCDEF',
        merchant: 'MERCHANT001',
        merchantName: 'Test Shop',
        description: 'Test payment',
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
    $request = new PaymentRequest(
        terminal: 'V1800001',
        trtype: '1',
        amount: '9.00',
        currency: 'BGN',
        order: '000001',
        timestamp: '20201012124757',
        nonce: 'AABBCCDD',
        pSign: 'ABCDEF',
        merchant: 'MERCHANT001',
        merchantName: 'Test Shop',
        description: 'Test payment',
        adCustBorOrderId: 'CUSTORDER1',
        country: 'BG',
        merchGmt: '+02:00',
        addendum: 'AD,TD',
        email: 'test@example.com',
        merchantUrl: 'https://example.com',
        language: 'EN',
        mInfo: 'base64data',
    );

    $data = $request->toArray();

    expect($data['AD.CUST_BOR_ORDER_ID'])->toBe('CUSTORDER1');
    expect($data['COUNTRY'])->toBe('BG');
    expect($data['MERCH_GMT'])->toBe('+02:00');
    expect($data['ADDENDUM'])->toBe('AD,TD');
    expect($data['EMAIL'])->toBe('test@example.com');
    expect($data['MERCH_URL'])->toBe('https://example.com');
    expect($data['LANG'])->toBe('EN');
    expect($data['M_INFO'])->toBe('base64data');
});

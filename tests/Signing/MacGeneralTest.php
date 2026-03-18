<?php
declare(strict_types=1);

use Ux2Dev\Borica\Enum\TransactionType;
use Ux2Dev\Borica\Signing\MacGeneral;

beforeEach(function () {
    $this->macGeneral = new MacGeneral();
});

test('request signing string for payment', function () {
    $fields = [
        'TERMINAL' => 'V1800001', 'TRTYPE' => '1', 'AMOUNT' => '9.00',
        'CURRENCY' => 'BGN', 'ORDER' => '154744', 'TIMESTAMP' => '20201012124757',
        'NONCE' => '9EADBD70C0A5AFBAD3DF405902602F79',
    ];
    $result = $this->macGeneral->buildRequestSigningData(TransactionType::Purchase, $fields);
    // TERMINAL(8+V1800001) TRTYPE(1+1) AMOUNT(4+9.00) CURRENCY(3+BGN) ORDER(6+154744)
    // TIMESTAMP(14+20201012124757) NONCE(32+9EADBD70C0A5AFBAD3DF405902602F79) RFU(-)
    $expected = '8V18000011149.003BGN61547441420201012124757329EADBD70C0A5AFBAD3DF405902602F79-';
    expect($result)->toBe($expected);
});

test('request signing string for preauth', function () {
    $fields = [
        'TERMINAL' => 'V1800001', 'TRTYPE' => '12', 'AMOUNT' => '100.50',
        'CURRENCY' => 'EUR', 'ORDER' => '000001', 'TIMESTAMP' => '20201012124757',
        'NONCE' => 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA1',
    ];
    $result = $this->macGeneral->buildRequestSigningData(TransactionType::PreAuth, $fields);
    $expected = '8V18000012126100.503EUR60000011420201012124757' .
        '32AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA1-';
    expect($result)->toBe($expected);
});

test('request signing string for status check has no rfu', function () {
    $fields = [
        'TERMINAL' => 'V1800001', 'TRTYPE' => '90',
        'ORDER' => '154744', 'NONCE' => '9EADBD70C0A5AFBAD3DF405902602F79',
    ];
    $result = $this->macGeneral->buildRequestSigningData(TransactionType::StatusCheck, $fields);
    $expected = '8V18000012906154744329EADBD70C0A5AFBAD3DF405902602F79';
    expect($result)->toBe($expected)->not->toEndWith('-');
});

test('response signing string with all fields', function () {
    $fields = [
        'ACTION' => '0', 'RC' => '00', 'APPROVAL' => 'A12345',
        'TERMINAL' => 'V1800001', 'TRTYPE' => '1', 'AMOUNT' => '9.00',
        'CURRENCY' => 'BGN', 'ORDER' => '154744', 'RRN' => '123456789012',
        'INT_REF' => '1234567890ABCDEF', 'PARES_STATUS' => 'Y', 'ECI' => '05',
        'TIMESTAMP' => '20201012124757', 'NONCE' => '9EADBD70C0A5AFBAD3DF405902602F79',
    ];
    $result = $this->macGeneral->buildResponseSigningData(TransactionType::Purchase, $fields);
    $expected = '10' . '200' . '6A12345' . '8V1800001' . '11' . '49.00' . '3BGN' .
        '6154744' . '12123456789012' . '161234567890ABCDEF' . '1Y' . '205' .
        '1420201012124757' . '329EADBD70C0A5AFBAD3DF405902602F79' . '-';
    expect($result)->toBe($expected);
});

test('response signing string with missing fields', function () {
    $fields = [
        'ACTION' => '2', 'RC' => '05', 'APPROVAL' => '',
        'TERMINAL' => 'V1800001', 'TRTYPE' => '1', 'AMOUNT' => '9.00',
        'CURRENCY' => 'BGN', 'ORDER' => '154744', 'RRN' => '', 'INT_REF' => '',
        'PARES_STATUS' => '', 'ECI' => '', 'TIMESTAMP' => '20201012124757',
        'NONCE' => '9EADBD70C0A5AFBAD3DF405902602F79',
    ];
    $result = $this->macGeneral->buildResponseSigningData(TransactionType::Purchase, $fields);
    expect($result)->toContain('12' . '205' . '-' . '8V1800001');
});

test('reversal request signing string', function () {
    $fields = [
        'TERMINAL' => 'V1800001', 'TRTYPE' => '24', 'AMOUNT' => '9.00',
        'CURRENCY' => 'BGN', 'ORDER' => '154744', 'TIMESTAMP' => '20201012124757',
        'NONCE' => '9EADBD70C0A5AFBAD3DF405902602F79',
    ];
    $result = $this->macGeneral->buildRequestSigningData(TransactionType::Reversal, $fields);
    // TERMINAL(8+V1800001) TRTYPE(2+24) AMOUNT(4+9.00) CURRENCY(3+BGN) ...
    $expected = '8V180000122449.003BGN61547441420201012124757329EADBD70C0A5AFBAD3DF405902602F79-';
    expect($result)->toBe($expected);
});

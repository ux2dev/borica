<?php

declare(strict_types=1);

use Ux2Dev\Borica\Cgi\Support\Validator;
use Ux2Dev\Borica\Exception\ConfigurationException;

test('amount must be positive with two decimal places', function () {
    Validator::amount('10.50'); // ok
    expect(fn () => Validator::amount('10'))->toThrow(ConfigurationException::class);
    expect(fn () => Validator::amount('10.5'))->toThrow(ConfigurationException::class);
    expect(fn () => Validator::amount('0.00'))->toThrow(ConfigurationException::class);
    expect(fn () => Validator::amount('-1.00'))->toThrow(ConfigurationException::class);
});

test('order must be exactly 6 digits', function () {
    Validator::order('000001'); // ok
    expect(fn () => Validator::order('1'))->toThrow(ConfigurationException::class);
    expect(fn () => Validator::order('1234567'))->toThrow(ConfigurationException::class);
    expect(fn () => Validator::order('abcdef'))->toThrow(ConfigurationException::class);
});

test('description must be 1-50 chars', function () {
    Validator::description('Test');
    expect(fn () => Validator::description(''))->toThrow(ConfigurationException::class);
    expect(fn () => Validator::description(str_repeat('a', 51)))->toThrow(ConfigurationException::class);
});

test('email accepts empty or valid email', function () {
    Validator::email('');
    Validator::email('user@example.com');
    expect(fn () => Validator::email('not-an-email'))->toThrow(ConfigurationException::class);
});

test('merchantUrl accepts empty or https URL', function () {
    Validator::merchantUrl('');
    Validator::merchantUrl('https://example.com');
    expect(fn () => Validator::merchantUrl('http://example.com'))->toThrow(ConfigurationException::class);
    expect(fn () => Validator::merchantUrl('not a url'))->toThrow(ConfigurationException::class);
});

test('mInfo must contain cardholderName and email or phone', function () {
    Validator::mInfo(['cardholderName' => 'John Doe', 'email' => 'j@e.com']);
    expect(fn () => Validator::mInfo([]))->toThrow(ConfigurationException::class);
    expect(fn () => Validator::mInfo(['cardholderName' => 'John']))->toThrow(ConfigurationException::class);
    expect(fn () => Validator::mInfo(['cardholderName' => str_repeat('a', 46), 'email' => 'j@e.com']))->toThrow(ConfigurationException::class);
});

test('resolveTimestamp returns input when valid, generates if null', function () {
    expect(Validator::resolveTimestamp('20260420120000'))->toBe('20260420120000');
    expect(Validator::resolveTimestamp(null))->toMatch('/^\d{14}$/');
    expect(fn () => Validator::resolveTimestamp('invalid'))->toThrow(ConfigurationException::class);
});

test('resolveNonce returns input when valid, generates if null', function () {
    $valid = str_repeat('A', 32);
    expect(Validator::resolveNonce($valid))->toBe($valid);
    expect(Validator::resolveNonce(null))->toMatch('/^[A-F0-9]{32}$/');
    expect(fn () => Validator::resolveNonce('too short'))->toThrow(ConfigurationException::class);
});

test('encodeMInfo returns base64-encoded JSON', function () {
    $encoded = Validator::encodeMInfo(['cardholderName' => 'John Doe', 'email' => 'j@e.com']);
    $decoded = json_decode(base64_decode($encoded), true);
    expect($decoded['cardholderName'])->toBe('John Doe');
});

test('resolveAdCustBorOrderId strips semicolons and caps to 22 chars', function () {
    expect(Validator::resolveAdCustBorOrderId('', '000001'))->toBe('000001');
    expect(Validator::resolveAdCustBorOrderId('my;order', '000001'))->toBe('myorder');
    expect(Validator::resolveAdCustBorOrderId(str_repeat('a', 30), '000001'))->toBe(str_repeat('a', 22));
});

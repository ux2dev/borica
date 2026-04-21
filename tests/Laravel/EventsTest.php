<?php

declare(strict_types=1);

use Ux2Dev\Borica\Laravel\Events\BoricaPaymentFailed;
use Ux2Dev\Borica\Laravel\Events\BoricaPaymentSucceeded;
use Ux2Dev\Borica\Laravel\Events\BoricaPreAuthFailed;
use Ux2Dev\Borica\Laravel\Events\BoricaPreAuthSucceeded;
use Ux2Dev\Borica\Laravel\Events\BoricaResponseReceived;
use Ux2Dev\Borica\Cgi\Response\Response;

test('BoricaResponseReceived holds response and terminal', function () {
    $response = new Response([
        'ACTION' => '0',
        'RC' => '00',
        'TERMINAL' => 'V1800001',
        'ORDER' => '000001',
        'TRTYPE' => '1',
        'TIMESTAMP' => '20240101120000',
        'NONCE' => 'abc123',
    ]);

    $event = new BoricaResponseReceived($response, 'V1800001', 'Test Shop');

    expect($event->response)->toBe($response)
        ->and($event->terminal)->toBe('V1800001')
        ->and($event->merchantName)->toBe('Test Shop');
});

test('BoricaResponseReceived accepts null merchant name', function () {
    $response = new Response([
        'ACTION' => '0',
        'RC' => '00',
        'TERMINAL' => 'V1800001',
        'ORDER' => '000001',
        'TRTYPE' => '1',
        'TIMESTAMP' => '20240101120000',
        'NONCE' => 'abc123',
    ]);

    $event = new BoricaResponseReceived($response, 'V1800001', null);

    expect($event->response)->toBe($response)
        ->and($event->terminal)->toBe('V1800001')
        ->and($event->merchantName)->toBeNull();
});

test('BoricaPaymentSucceeded holds response and merchant name', function () {
    $response = new Response([
        'ACTION' => '0',
        'RC' => '00',
        'TERMINAL' => 'V1800001',
        'ORDER' => '000002',
        'TRTYPE' => '1',
        'TIMESTAMP' => '20240101120000',
        'NONCE' => 'def456',
    ]);

    $event = new BoricaPaymentSucceeded($response, 'Test Shop');

    expect($event->response)->toBe($response)
        ->and($event->merchantName)->toBe('Test Shop');
});

test('BoricaPaymentFailed holds response and merchant name', function () {
    $response = new Response([
        'ACTION' => '1',
        'RC' => '-17',
        'TERMINAL' => 'V1800001',
        'ORDER' => '000003',
        'TRTYPE' => '1',
        'TIMESTAMP' => '20240101120000',
        'NONCE' => 'ghi789',
    ]);

    $event = new BoricaPaymentFailed($response, 'Test Shop');

    expect($event->response)->toBe($response)
        ->and($event->merchantName)->toBe('Test Shop');
});

test('BoricaPreAuthSucceeded holds response and merchant name', function () {
    $response = new Response([
        'ACTION' => '0',
        'RC' => '00',
        'TERMINAL' => 'V1800001',
        'ORDER' => '000004',
        'TRTYPE' => '12',
        'TIMESTAMP' => '20240101120000',
        'NONCE' => 'jkl012',
    ]);

    $event = new BoricaPreAuthSucceeded($response, 'Test Shop');

    expect($event->response)->toBe($response)
        ->and($event->merchantName)->toBe('Test Shop');
});

test('BoricaPreAuthFailed holds response and merchant name', function () {
    $response = new Response([
        'ACTION' => '1',
        'RC' => '-17',
        'TERMINAL' => 'V1800001',
        'ORDER' => '000005',
        'TRTYPE' => '12',
        'TIMESTAMP' => '20240101120000',
        'NONCE' => 'mno345',
    ]);

    $event = new BoricaPreAuthFailed($response, null);

    expect($event->response)->toBe($response)
        ->and($event->merchantName)->toBeNull();
});

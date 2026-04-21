<?php

declare(strict_types=1);

use Ux2Dev\Borica\InfopayCheckout\Dto\PaymentRequestResult;
use Ux2Dev\Borica\InfopayCheckout\Dto\PaymentStatus;
use Ux2Dev\Borica\InfopayCheckout\Enum\PaymentRequestStatusCode;
use Ux2Dev\Borica\InfopayCheckout\Enum\PaymentStatusCode;

test('PaymentRequestResult parses API response', function () {
    $result = PaymentRequestResult::fromArray([
        'paymentRequestId' => '123abc',
        '_links' => [
            'checkoutURL' => ['href' => 'https://checkout.example/123'],
            'requestStatusURL' => ['href' => 'https://api.example/status/123'],
        ],
    ]);

    expect($result->paymentRequestId)->toBe('123abc');
    expect($result->checkoutUrl)->toBe('https://checkout.example/123');
    expect($result->requestStatusUrl)->toBe('https://api.example/status/123');
});

test('PaymentStatus parses PaymentRequestStatus + PaymentStatus sections', function () {
    $status = PaymentStatus::fromArray([
        'status' => [
            'PaymentRequestStatus' => [
                'Code' => 'PaymentCreated',
                'IsFinal' => false,
            ],
            'PaymentStatus' => [
                'Code' => 'Processed',
                'IsFinal' => true,
            ],
        ],
    ]);

    expect($status->paymentRequestStatus?->code)->toBe(PaymentRequestStatusCode::PaymentCreated);
    expect($status->paymentRequestStatus?->isFinal)->toBeFalse();
    expect($status->paymentStatus?->code)->toBe(PaymentStatusCode::Processed);
    expect($status->paymentStatus?->isFinal)->toBeTrue();
});

test('PaymentStatus handles missing PaymentStatus section (payment not yet created)', function () {
    $status = PaymentStatus::fromArray([
        'status' => [
            'PaymentRequestStatus' => [
                'Code' => 'New',
                'IsFinal' => false,
            ],
        ],
    ]);

    expect($status->paymentRequestStatus?->code)->toBe(PaymentRequestStatusCode::New);
    expect($status->paymentStatus)->toBeNull();
});

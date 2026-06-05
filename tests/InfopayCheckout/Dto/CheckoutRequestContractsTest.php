<?php

declare(strict_types=1);

use Ux2Dev\Borica\Contracts\ApiRequest;
use Ux2Dev\Borica\InfopayCheckout\Dto\CreateSessionRequest;
use Ux2Dev\Borica\InfopayCheckout\Dto\PaymentRequestDto;
use Ux2Dev\Borica\InfopayCheckout\Dto\PaymentRequestResult;
use Ux2Dev\Borica\InfopayCheckout\Dto\Session;

test('CreateSessionRequest is an ApiRequest', function () {
    $r = new CreateSessionRequest('auth-id', 'secret');

    expect($r)->toBeInstanceOf(ApiRequest::class);
    expect($r->method())->toBe('POST');
    expect($r->endpoint())->toBe('/v1/api/sessions');
    expect($r->responseClass())->toBe(Session::class);
    expect($r->toArray())->toBe(['authId' => 'auth-id', 'authSecret' => 'secret']);
});

test('PaymentRequestDto is an ApiRequest describing the paymentRequests endpoint', function () {
    expect(is_subclass_of(PaymentRequestDto::class, ApiRequest::class))->toBeTrue();
    $r = new PaymentRequestDto(
        shopId: 'shop-1',
        beneficiaryDefaultAccount: new \Ux2Dev\Borica\InfopayCheckout\Dto\Account('BG29RZBB91550123456789'),
        instructedAmount: new \Ux2Dev\Borica\InfopayCheckout\Dto\InstructedAmount(150.00, \Ux2Dev\Borica\InfopayCheckout\Enum\InstructedAmountCurrency::Bgn),
        details: 'Order 1',
        validTime: new DateTimeImmutable('2026-12-31T23:59:59Z'),
        externalReferenceId: 'ext-1',
        paymentDetails: new \Ux2Dev\Borica\InfopayCheckout\Dto\DomesticCreditTransferBgn('Invoice 123'),
    );

    expect($r->method())->toBe('POST');
    expect($r->endpoint())->toBe('/v1/api/paymentRequests');
    expect($r->responseClass())->toBe(PaymentRequestResult::class);
});

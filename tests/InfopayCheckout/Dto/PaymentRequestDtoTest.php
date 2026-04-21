<?php

declare(strict_types=1);

use Ux2Dev\Borica\InfopayCheckout\Dto\Account;
use Ux2Dev\Borica\InfopayCheckout\Dto\DomesticCreditTransferBgn;
use Ux2Dev\Borica\InfopayCheckout\Dto\InstructedAmount;
use Ux2Dev\Borica\InfopayCheckout\Dto\PaymentRequestDto;
use Ux2Dev\Borica\InfopayCheckout\Enum\InstructedAmountCurrency;
use Ux2Dev\Borica\InfopayCheckout\Enum\PaymentLanguage;

test('PaymentRequestDto toArray produces minimal payload with required fields', function () {
    $dto = new PaymentRequestDto(
        shopId: '69e1dbb5-1d28-4059-a5a4-b1b56b84a86d',
        beneficiaryDefaultAccount: new Account('BG29RZBB91550123456789'),
        instructedAmount: new InstructedAmount(150.00, InstructedAmountCurrency::Bgn),
        details: 'Order No 5679',
        validTime: new DateTimeImmutable('2024-12-31T23:59:59Z'),
        externalReferenceId: '40caf02c-b556-4001-bfb8-12c039fa35d4',
        paymentDetails: new DomesticCreditTransferBgn('Pay Invoice 123'),
    );

    $payload = $dto->toArray();

    expect($payload['shopId'])->toBe('69e1dbb5-1d28-4059-a5a4-b1b56b84a86d');
    expect($payload['beneficiaryDefaultAccount'])->toBe(['iban' => 'BG29RZBB91550123456789']);
    expect($payload['instructedAmount'])->toBe(['amount' => 150.00, 'currency' => 'BGN']);
    expect($payload['details'])->toBe('Order No 5679');
    expect($payload['validTime'])->toBe('2024-12-31T23:59:59+00:00');
    expect($payload['externalReferenceId'])->toBe('40caf02c-b556-4001-bfb8-12c039fa35d4');
    expect($payload['paymentDetails']['type'])->toBe('domestic-credit-transfers-bgn');
});

test('PaymentRequestDto serialises optional fields when set', function () {
    $dto = new PaymentRequestDto(
        shopId: 'shop-1',
        beneficiaryDefaultAccount: new Account('BG29RZBB91550123456789'),
        instructedAmount: new InstructedAmount(10.0, InstructedAmountCurrency::Eur),
        details: 'Test',
        validTime: new DateTimeImmutable('2026-01-01T00:00:00Z'),
        externalReferenceId: 'ref-1',
        paymentDetails: new DomesticCreditTransferBgn('pay'),
        beneficiaryAlternativeAccounts: [new Account('BG80BNBG96611020345678')],
        successUrl: 'https://m.example/success',
        errorUrl: 'https://m.example/error',
        language: PaymentLanguage::Bg,
    );

    $payload = $dto->toArray();

    expect($payload['beneficiaryAlternativeAccounts'])->toBe([['iban' => 'BG80BNBG96611020345678']]);
    expect($payload['successURL'])->toBe('https://m.example/success');
    expect($payload['errorURL'])->toBe('https://m.example/error');
    expect($payload['language'])->toBe('BG');
});

test('PaymentRequestDto omits optional fields when null/empty', function () {
    $dto = new PaymentRequestDto(
        shopId: 'shop-1',
        beneficiaryDefaultAccount: new Account('BG29RZBB91550123456789'),
        instructedAmount: new InstructedAmount(10.0, InstructedAmountCurrency::Eur),
        details: 'Test',
        validTime: new DateTimeImmutable('2026-01-01T00:00:00Z'),
        externalReferenceId: 'ref-1',
        paymentDetails: new DomesticCreditTransferBgn('pay'),
    );

    $payload = $dto->toArray();

    expect($payload)->not->toHaveKey('beneficiaryAlternativeAccounts');
    expect($payload)->not->toHaveKey('successURL');
    expect($payload)->not->toHaveKey('errorURL');
    expect($payload)->not->toHaveKey('language');
});

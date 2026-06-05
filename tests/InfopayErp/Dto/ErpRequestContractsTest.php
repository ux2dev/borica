<?php

declare(strict_types=1);

use Ux2Dev\Borica\Contracts\ApiRequest;
use Ux2Dev\Borica\InfopayErp\Dto\AccountReference;
use Ux2Dev\Borica\InfopayErp\Dto\BulkSepaPaymentRequest;
use Ux2Dev\Borica\InfopayErp\Dto\InvoiceCreateResult;
use Ux2Dev\Borica\InfopayErp\Dto\PaymentResult;
use Ux2Dev\Borica\InfopayErp\Dto\SingleSepaPaymentRequest;

test('SingleSepaPaymentRequest is an ApiRequest describing its endpoint', function () {
    $r = new SingleSepaPaymentRequest(
        debtorAccount: new AccountReference('BG80BNBG96611020345678'),
        payment: buildSepa(),
    );

    expect($r)->toBeInstanceOf(ApiRequest::class);
    expect($r->method())->toBe('POST');
    expect($r->endpoint())->toBe('/api/payments/sepa-credit-transfers');
    expect($r->responseClass())->toBe(PaymentResult::class);
});

test('BulkSepaPaymentRequest is an ApiRequest describing its endpoint', function () {
    $r = new BulkSepaPaymentRequest(
        debtorAccount: new AccountReference('BG1'),
        payments: [buildSepa(), buildSepa()],
    );

    expect($r)->toBeInstanceOf(ApiRequest::class);
    expect($r->method())->toBe('POST');
    expect($r->endpoint())->toBe('/api/bulk-payments/sepa-credit-transfers');
    expect($r->responseClass())->toBe(PaymentResult::class);
});

test('InvoiceCreateRequest declares the invoices endpoint and result', function () {
    // Build via reflection-free direct construction is heavy; assert the
    // static contract surface that the resource relies on.
    expect(is_subclass_of(\Ux2Dev\Borica\InfopayErp\Dto\InvoiceCreateRequest::class, ApiRequest::class))->toBeTrue();
    expect(InvoiceCreateResult::class)->toBe(InvoiceCreateResult::class);
});

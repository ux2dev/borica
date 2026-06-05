<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\HttpFactory;
use Ux2Dev\Borica\Cgi\CgiArea;
use Ux2Dev\Borica\Cgi\Resource\PaymentsResource as CgiPayments;
use Ux2Dev\Borica\Cgi\Resource\ResponsesResource;
use Ux2Dev\Borica\InfopayCheckout\CheckoutArea;
use Ux2Dev\Borica\InfopayCheckout\Resource\PaymentRequestsResource;
use Ux2Dev\Borica\InfopayCheckout\Resource\SessionsResource as CheckoutSessions;
use Ux2Dev\Borica\InfopayErp\ErpArea;
use Ux2Dev\Borica\InfopayErp\Resource\AccountsResource;
use Ux2Dev\Borica\InfopayErp\Resource\PaymentsResource as ErpPayments;
use Ux2Dev\Borica\InfopayErp\Resource\SessionsResource as ErpSessions;

test('CgiArea lazily exposes and caches CGI resources', function () {
    $area = new CgiArea(test_merchant_config());
    expect($area->payments())->toBeInstanceOf(CgiPayments::class);
    expect($area->payments())->toBe($area->payments());
    expect($area->responses())->toBeInstanceOf(ResponsesResource::class);
});

test('CheckoutArea lazily exposes and caches Checkout resources', function () {
    $f = new HttpFactory();
    $area = new CheckoutArea(test_checkout_config(), test_psr18_client(), $f, $f);
    expect($area->sessions())->toBeInstanceOf(CheckoutSessions::class);
    expect($area->sessions())->toBe($area->sessions());
    expect($area->paymentRequests())->toBeInstanceOf(PaymentRequestsResource::class);
});

test('ErpArea lazily exposes and caches all ERP resources', function () {
    $f = new HttpFactory();
    $area = new ErpArea(test_erp_config(), test_psr18_client(), $f, $f);
    expect($area->sessions())->toBeInstanceOf(ErpSessions::class);
    expect($area->sessions())->toBe($area->sessions());
    expect($area->accounts())->toBeInstanceOf(AccountsResource::class);
    expect($area->payments())->toBeInstanceOf(ErpPayments::class);
    foreach (['synchronizations', 'transactions', 'bulkPayments', 'invoices'] as $m) {
        expect($area->{$m}())->not->toBeNull();
    }
});

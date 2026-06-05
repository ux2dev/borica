<?php

declare(strict_types=1);

use Ux2Dev\Borica\Cgi\CgiArea;
use Ux2Dev\Borica\Cgi\Request\PaymentRequest;
use Ux2Dev\Borica\Laravel\BoricaManager;
use Ux2Dev\Borica\Laravel\Facades\Borica;

test('facade resolves to BoricaManager', function () {
    expect(Borica::getFacadeRoot())->toBeInstanceOf(BoricaManager::class);
});

test('facade proxies cgi() to the active tenant', function () {
    expect(Borica::cgi())->toBeInstanceOf(CgiArea::class);
});

test('facade proxies cgi()->getGatewayUrl()', function () {
    expect(Borica::cgi()->getGatewayUrl())->toBe('https://3dsgate-dev.borica.bg/cgi-bin/cgi_link');
});

test('facade proxies cgi()->payments()->purchase()', function () {
    $request = Borica::cgi()->payments()->purchase(new \Ux2Dev\Borica\Cgi\Request\Input\PaymentInput(
        amount: '9.00',
        order: '000001',
        description: 'Test',
        mInfo: ['cardholderName' => 'John', 'email' => 'john@test.com'],
    ));

    expect($request)->toBeInstanceOf(PaymentRequest::class);
    expect($request->toArray()['AMOUNT'])->toBe('9.00');
});

test('facade exposes tenant switching', function () {
    expect(Borica::tenant('default'))->toBeInstanceOf(BoricaManager::class);
    expect(Borica::currentTenant())->toBe('default');
});

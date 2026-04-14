<?php
declare(strict_types=1);

use Ux2Dev\Borica\Laravel\BoricaManager;
use Ux2Dev\Borica\Laravel\Facades\Borica;
use Ux2Dev\Borica\Request\PaymentRequest;

test('facade resolves to BoricaManager', function () {
    expect(Borica::getFacadeRoot())->toBeInstanceOf(BoricaManager::class);
});

test('facade proxies getGatewayUrl', function () {
    expect(Borica::getGatewayUrl())->toBe('https://3dsgate-dev.borica.bg/cgi-bin/cgi_link');
});

test('facade proxies createPaymentRequest', function () {
    $request = Borica::createPaymentRequest(
        amount: '9.00',
        order: '000001',
        description: 'Test',
        mInfo: ['cardholderName' => 'John', 'email' => 'john@test.com'],
    );

    expect($request)->toBeInstanceOf(PaymentRequest::class);
});

test('facade proxies merchant method', function () {
    $borica = Borica::merchant('default');

    expect($borica)->toBeInstanceOf(\Ux2Dev\Borica\Borica::class);
});

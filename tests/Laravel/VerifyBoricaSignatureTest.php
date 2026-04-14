<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Ux2Dev\Borica\Enum\TransactionType;
use Ux2Dev\Borica\Laravel\Events\BoricaResponseReceived;
use Ux2Dev\Borica\Laravel\Http\Middleware\VerifyBoricaSignature;
use Ux2Dev\Borica\Response\Response as BoricaResponse;

test('valid signature attaches Response to request', function () {
    $data = $this->buildSignedCallbackData();

    $request = Request::create('/borica/callback', 'POST', $data);

    $middleware = app(VerifyBoricaSignature::class);

    $middleware->handle($request, function (Request $req) {
        expect($req->attributes->get('borica_response'))
            ->toBeInstanceOf(BoricaResponse::class)
            ->and($req->attributes->get('borica_merchant_name'))
            ->toBe('default')
            ->and($req->attributes->get('borica_transaction_type'))
            ->toBe(TransactionType::Purchase);

        return new \Illuminate\Http\Response('OK');
    });
});

test('invalid P_SIGN aborts with 403', function () {
    $data = $this->buildSignedCallbackData();
    $data['P_SIGN'] = str_repeat('A', 512);

    $request = Request::create('/borica/callback', 'POST', $data);

    $middleware = app(VerifyBoricaSignature::class);

    $middleware->handle($request, function () {
        return new \Illuminate\Http\Response('OK');
    });
})->throws(HttpException::class);

test('missing P_SIGN aborts with 403', function () {
    $data = $this->buildSignedCallbackData();
    unset($data['P_SIGN']);

    $request = Request::create('/borica/callback', 'POST', $data);

    $middleware = app(VerifyBoricaSignature::class);

    $middleware->handle($request, function () {
        return new \Illuminate\Http\Response('OK');
    });
})->throws(HttpException::class);

test('unknown terminal aborts with 403 without dispatching event', function () {
    Event::fake();

    $data = $this->buildSignedCallbackData(['TERMINAL' => 'UNKNOWN1']);

    $request = Request::create('/borica/callback', 'POST', $data);

    $middleware = app(VerifyBoricaSignature::class);

    try {
        $middleware->handle($request, function () {
            return new \Illuminate\Http\Response('OK');
        });
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(403);
    }

    Event::assertNotDispatched(BoricaResponseReceived::class);
});

test('query parameters do not override POST data', function () {
    $data = $this->buildSignedCallbackData();

    // Attacker appends query params to callback URL
    $request = Request::create(
        '/borica/callback?STATUSMSG=Hacked&CARD_BRAND=AMEX',
        'POST',
        $data,
    );

    $middleware = app(VerifyBoricaSignature::class);

    $middleware->handle($request, function (Request $req) {
        $response = $req->attributes->get('borica_response');

        // Query params must NOT appear in the verified response
        expect($response->getStatusMessage())->toBeNull();
        expect($response->getCardBrand())->toBeNull();

        return new \Illuminate\Http\Response('OK');
    });
});

test('tampered amount aborts with 403', function () {
    $data = $this->buildSignedCallbackData();
    $data['AMOUNT'] = '999.00';

    $request = Request::create('/borica/callback', 'POST', $data);

    $middleware = app(VerifyBoricaSignature::class);

    $middleware->handle($request, function () {
        return new \Illuminate\Http\Response('OK');
    });
})->throws(HttpException::class);

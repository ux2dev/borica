<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Ux2Dev\Borica\Laravel\Events\BoricaPaymentFailed;
use Ux2Dev\Borica\Laravel\Events\BoricaPaymentSucceeded;
use Ux2Dev\Borica\Laravel\Events\BoricaPreAuthFailed;
use Ux2Dev\Borica\Laravel\Events\BoricaPreAuthSucceeded;
use Ux2Dev\Borica\Laravel\Events\BoricaResponseReceived;

test('successful payment dispatches events and redirects to success', function () {
    Event::fake();

    $data = $this->buildSignedCallbackData([
        'ACTION' => '0',
        'RC' => '00',
        'TRTYPE' => '1',
    ]);

    $response = $this->post('/borica/callback', $data);

    $response->assertRedirect('/payment/success');

    Event::assertDispatched(BoricaResponseReceived::class);
    Event::assertDispatched(BoricaPaymentSucceeded::class);
    Event::assertNotDispatched(BoricaPaymentFailed::class);
});

test('failed payment dispatches events and redirects to failure', function () {
    Event::fake();

    $data = $this->buildSignedCallbackData([
        'ACTION' => '3',
        'RC' => '13',
        'TRTYPE' => '1',
    ]);

    $response = $this->post('/borica/callback', $data);

    $response->assertRedirect('/payment/failure');

    Event::assertDispatched(BoricaPaymentFailed::class);
    Event::assertNotDispatched(BoricaPaymentSucceeded::class);
});

test('successful pre-auth dispatches BoricaPreAuthSucceeded', function () {
    Event::fake();

    $data = $this->buildSignedCallbackData([
        'ACTION' => '0',
        'RC' => '00',
        'TRTYPE' => '12',
    ]);

    $response = $this->post('/borica/callback', $data);

    $response->assertRedirect('/payment/success');

    Event::assertDispatched(BoricaPreAuthSucceeded::class);
    Event::assertNotDispatched(BoricaPreAuthFailed::class);
    Event::assertNotDispatched(BoricaPaymentSucceeded::class);
});

test('failed pre-auth dispatches BoricaPreAuthFailed', function () {
    Event::fake();

    $data = $this->buildSignedCallbackData([
        'ACTION' => '3',
        'RC' => '13',
        'TRTYPE' => '12',
    ]);

    $response = $this->post('/borica/callback', $data);

    $response->assertRedirect('/payment/failure');

    Event::assertDispatched(BoricaPreAuthFailed::class);
});

test('callback route is registered with correct name', function () {
    $route = app('router')->getRoutes()->getByName('borica.callback');

    expect($route)->not->toBeNull()
        ->and($route->uri())->toBe('borica/callback')
        ->and($route->methods())->toContain('POST');
});

test('callback route uses configured prefix', function () {
    app('config')->set('borica.routes.prefix', 'payments/borica');

    // Clear existing routes and re-register with new prefix
    $router = app('router');
    $router->setRoutes(new \Illuminate\Routing\RouteCollection());

    (new \Ux2Dev\Borica\Laravel\BoricaServiceProvider(app()))->boot();

    $allRoutes = $router->getRoutes()->getRoutes();
    $found = null;
    foreach ($allRoutes as $route) {
        if ($route->uri() === 'payments/borica/callback') {
            $found = $route;
            break;
        }
    }

    expect($found)->not->toBeNull()
        ->and($found->uri())->toBe('payments/borica/callback')
        ->and($found->methods())->toContain('POST')
        ->and($found->getName())->toBe('borica.callback');
});

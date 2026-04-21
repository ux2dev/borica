<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;
use Ux2Dev\Borica\Laravel\BoricaManager;
use Ux2Dev\Borica\Laravel\BoricaServiceProvider;

test('config is merged', function () {
    expect(config('borica'))->toBeArray();
    expect(config('borica.cgi.default'))->toBe('default');
});

test('BoricaManager is registered as singleton', function () {
    $first = app(BoricaManager::class);
    $second = app(BoricaManager::class);

    expect($first)->toBe($second);
});

test('callback route is registered when routes enabled', function () {
    $route = app('router')->getRoutes()->getByName('borica.callback');

    expect($route)->not->toBeNull();
});

test('callback route is not registered when routes disabled', function () {
    $app = app();
    $app['config']->set('borica.routes.enabled', false);

    $provider = new BoricaServiceProvider($app);

    $routesBefore = $app['router']->getRoutes()->count();

    $method = new ReflectionMethod(BoricaServiceProvider::class, 'loadRoutes');
    $method->setAccessible(true);
    $method->invoke($provider);

    $routesAfter = $app['router']->getRoutes()->count();

    expect($routesAfter)->toBe($routesBefore);
});

test('artisan commands are registered', function () {
    $commands = Artisan::all();

    expect($commands)->toHaveKey('borica:generate-certificate');
    expect($commands)->toHaveKey('borica:status-check');
});

test('config is publishable', function () {
    $paths = ServiceProvider::pathsToPublish(BoricaServiceProvider::class, 'borica-config');

    expect($paths)->not->toBeEmpty();
});

test('routes are publishable', function () {
    $paths = ServiceProvider::pathsToPublish(BoricaServiceProvider::class, 'borica-routes');

    expect($paths)->not->toBeEmpty();
});

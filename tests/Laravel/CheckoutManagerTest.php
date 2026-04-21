<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Ux2Dev\Borica\InfopayCheckout\CheckoutClient;
use Ux2Dev\Borica\Laravel\BoricaManager;

function checkoutTestClient(\Psr\Http\Message\ResponseInterface $res): ClientInterface
{
    return new class($res) implements ClientInterface {
        public function __construct(private $res) {}
        public function sendRequest(RequestInterface $r): \Psr\Http\Message\ResponseInterface { return $this->res; }
    };
}

test('BoricaManager::checkout returns CheckoutClient from config', function () {
    $privateKey = file_get_contents(__DIR__ . '/../fixtures/test_private_key.pem');
    $certificate = file_get_contents(__DIR__ . '/../fixtures/test_certificate.pem');

    config()->set('borica.checkout.merchants.default', [
        'base_url' => 'https://uat-api-checkout.infopay.bg',
        'auth_id' => 'aid',
        'auth_secret' => 'asec',
        'shop_id' => 'sid',
        'private_key' => $privateKey,
        'certificate' => $certificate,
    ]);

    app()->bind(ClientInterface::class, fn () => checkoutTestClient(new Psr7Response(200, [], '{}')));
    app()->bind(\Psr\Http\Message\RequestFactoryInterface::class, fn () => new HttpFactory());
    app()->bind(\Psr\Http\Message\StreamFactoryInterface::class, fn () => new HttpFactory());

    $manager = app(BoricaManager::class);
    $client = $manager->checkout();

    expect($client)->toBeInstanceOf(CheckoutClient::class);
});

test('BoricaManager::checkout caches the client per name', function () {
    $privateKey = file_get_contents(__DIR__ . '/../fixtures/test_private_key.pem');
    $certificate = file_get_contents(__DIR__ . '/../fixtures/test_certificate.pem');
    config()->set('borica.checkout.merchants.default', [
        'base_url' => 'https://uat-api-checkout.infopay.bg',
        'auth_id' => 'aid',
        'auth_secret' => 'asec',
        'shop_id' => 'sid',
        'private_key' => $privateKey,
        'certificate' => $certificate,
    ]);
    app()->bind(ClientInterface::class, fn () => checkoutTestClient(new Psr7Response(200, [], '{}')));
    app()->bind(\Psr\Http\Message\RequestFactoryInterface::class, fn () => new HttpFactory());
    app()->bind(\Psr\Http\Message\StreamFactoryInterface::class, fn () => new HttpFactory());

    $manager = app(BoricaManager::class);
    expect($manager->checkout())->toBe($manager->checkout());
});

test('BoricaManager::checkout throws for unknown merchant', function () {
    $manager = app(BoricaManager::class);
    $manager->checkout('no-such');
})->throws(\InvalidArgumentException::class);

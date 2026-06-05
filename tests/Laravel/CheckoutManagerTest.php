<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Ux2Dev\Borica\Exception\ConfigurationException;
use Ux2Dev\Borica\InfopayCheckout\CheckoutArea;
use Ux2Dev\Borica\Laravel\BoricaManager;

function checkoutTestClient(\Psr\Http\Message\ResponseInterface $res): ClientInterface
{
    return new class($res) implements ClientInterface {
        public function __construct(private $res) {}
        public function sendRequest(RequestInterface $r): \Psr\Http\Message\ResponseInterface { return $this->res; }
    };
}

function configureCheckoutTenant(): void
{
    config()->set('borica.tenants.default.checkout', [
        'base_url' => 'https://uat-api-checkout.infopay.bg',
        'auth_id' => 'aid',
        'auth_secret' => 'asec',
        'shop_id' => 'sid',
        'private_key' => file_get_contents(__DIR__ . '/../fixtures/test_private_key.pem'),
        'certificate' => file_get_contents(__DIR__ . '/../fixtures/test_certificate.pem'),
    ]);
    app()->bind(ClientInterface::class, fn () => checkoutTestClient(new Psr7Response(200, [], '{}')));
    app()->bind(\Psr\Http\Message\RequestFactoryInterface::class, fn () => new HttpFactory());
    app()->bind(\Psr\Http\Message\StreamFactoryInterface::class, fn () => new HttpFactory());
}

test('Borica::checkout returns a CheckoutArea from tenant config', function () {
    configureCheckoutTenant();

    $manager = app(BoricaManager::class);

    expect($manager->checkout())->toBeInstanceOf(CheckoutArea::class);
});

test('Borica::checkout is cached via the per-tenant Borica instance', function () {
    configureCheckoutTenant();

    $manager = app(BoricaManager::class);

    expect($manager->checkout())->toBe($manager->checkout());
});

test('Borica::checkout throws when the tenant has no checkout config', function () {
    // Default tenant in TestCase configures only CGI.
    $manager = app(BoricaManager::class);
    $manager->checkout();
})->throws(ConfigurationException::class);

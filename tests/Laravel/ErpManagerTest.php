<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Ux2Dev\Borica\Exception\ConfigurationException;
use Ux2Dev\Borica\InfopayErp\ErpArea;
use Ux2Dev\Borica\Laravel\BoricaManager;

function erpTestClient(\Psr\Http\Message\ResponseInterface $res): ClientInterface
{
    return new class($res) implements ClientInterface {
        public function __construct(private $res) {}
        public function sendRequest(RequestInterface $r): \Psr\Http\Message\ResponseInterface { return $this->res; }
    };
}

beforeEach(function () {
    config()->set('borica.tenants.default.erp', [
        'base_url' => 'https://integration.infopay.bg',
        'unique_id' => 'a78941c2-3fab-428f-b614-1422b42a0e46',
        'access_token' => 'test-access-token',
    ]);
    app()->bind(ClientInterface::class, fn () => erpTestClient(new Psr7Response(200, [], '{}')));
    app()->bind(\Psr\Http\Message\RequestFactoryInterface::class, fn () => new HttpFactory());
    app()->bind(\Psr\Http\Message\StreamFactoryInterface::class, fn () => new HttpFactory());
});

test('Borica::erp returns an ErpArea from tenant config', function () {
    expect(app(BoricaManager::class)->erp())->toBeInstanceOf(ErpArea::class);
});

test('Borica::erp is cached via the per-tenant Borica instance', function () {
    $manager = app(BoricaManager::class);
    expect($manager->erp())->toBe($manager->erp());
});

test('Borica::erp throws for an unknown tenant', function () {
    app(BoricaManager::class)->tenant('no-such')->erp();
})->throws(ConfigurationException::class);

test('a tenant without erp config throws when erp() is accessed', function () {
    config()->set('borica.tenants.cgi-only', [
        'cgi' => [
            'terminal' => 'V1800002',
            'merchant_id' => 'MERCHANT02',
            'merchant_name' => 'Other',
            'private_key' => file_get_contents(__DIR__ . '/../fixtures/test_private_key.pem'),
        ],
    ]);
    app(BoricaManager::class)->tenant('cgi-only')->erp();
})->throws(ConfigurationException::class);

<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Ux2Dev\Borica\InfopayErp\ErpClient;
use Ux2Dev\Borica\Laravel\BoricaManager;

function erpTestClient(\Psr\Http\Message\ResponseInterface $res): ClientInterface
{
    return new class($res) implements ClientInterface {
        public function __construct(private $res) {}
        public function sendRequest(RequestInterface $r): \Psr\Http\Message\ResponseInterface { return $this->res; }
    };
}

beforeEach(function () {
    config()->set('borica.erp.integrations.default', [
        'base_url' => 'https://integration.infopay.bg',
        'unique_id' => 'a78941c2-3fab-428f-b614-1422b42a0e46',
        'access_token' => 'test-access-token',
    ]);
    app()->bind(ClientInterface::class, fn () => erpTestClient(new Psr7Response(200, [], '{}')));
    app()->bind(\Psr\Http\Message\RequestFactoryInterface::class, fn () => new HttpFactory());
    app()->bind(\Psr\Http\Message\StreamFactoryInterface::class, fn () => new HttpFactory());
});

test('BoricaManager::erp returns ErpClient from config', function () {
    expect(app(BoricaManager::class)->erp())->toBeInstanceOf(ErpClient::class);
});

test('BoricaManager::erp caches the client per integration name', function () {
    $manager = app(BoricaManager::class);
    expect($manager->erp())->toBe($manager->erp());
});

test('BoricaManager::erp throws for unknown integration', function () {
    app(BoricaManager::class)->erp('no-such');
})->throws(\InvalidArgumentException::class);

test('BoricaManager::erp accepts inline config array', function () {
    $client = app(BoricaManager::class)->erp([
        'base_url' => 'https://integration.infopay.bg',
        'unique_id' => 'inline-id',
        'access_token' => 'inline-token',
    ]);

    expect($client)->toBeInstanceOf(ErpClient::class);
});

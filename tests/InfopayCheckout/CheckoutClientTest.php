<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Ux2Dev\Borica\InfopayCheckout\CheckoutClient;
use Ux2Dev\Borica\InfopayCheckout\Config\CheckoutConfig;
use Ux2Dev\Borica\InfopayCheckout\Resource\PaymentRequestsResource;
use Ux2Dev\Borica\InfopayCheckout\Resource\SessionsResource;

function checkoutStubClient(\Psr\Http\Message\ResponseInterface $res): ClientInterface
{
    return new class($res) implements ClientInterface {
        public function __construct(private $res) {}
        public function sendRequest(RequestInterface $r): \Psr\Http\Message\ResponseInterface { return $this->res; }
    };
}

beforeEach(function () {
    $this->config = new CheckoutConfig(
        baseUrl: 'https://uat-api-checkout.infopay.bg',
        authId: 'a',
        authSecret: 'b',
        shopId: 's',
        privateKey: file_get_contents(__DIR__ . '/../fixtures/test_private_key.pem'),
        certificate: file_get_contents(__DIR__ . '/../fixtures/test_certificate.pem'),
    );
    $factory = new HttpFactory();
    $this->client = new CheckoutClient(
        config: $this->config,
        httpClient: checkoutStubClient(new Psr7Response(200, [], '{}')),
        requestFactory: $factory,
        streamFactory: $factory,
    );
});

test('sessions returns SessionsResource', function () {
    expect($this->client->sessions())->toBeInstanceOf(SessionsResource::class);
});

test('paymentRequests returns PaymentRequestsResource', function () {
    expect($this->client->paymentRequests())->toBeInstanceOf(PaymentRequestsResource::class);
});

test('same resource instance returned on repeated calls', function () {
    expect($this->client->sessions())->toBe($this->client->sessions());
    expect($this->client->paymentRequests())->toBe($this->client->paymentRequests());
});

test('end-to-end sessions->create against stubbed PSR-18 client', function () {
    $factory = new HttpFactory();
    $client = new CheckoutClient(
        config: $this->config,
        httpClient: checkoutStubClient(new Psr7Response(200, [], json_encode([
            'sessionId' => 'sid',
            'sessionKey' => 'skey',
            'status' => 'Success',
        ]))),
        requestFactory: $factory,
        streamFactory: $factory,
    );

    $session = $client->sessions()->create('a', 'b');
    expect($session->sessionId)->toBe('sid');
});

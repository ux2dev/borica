<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Ux2Dev\Borica\InfopayCheckout\Config\CheckoutConfig;
use Ux2Dev\Borica\InfopayCheckout\Dto\Session;
use Ux2Dev\Borica\InfopayCheckout\Enum\SessionCreateStatus;
use Ux2Dev\Borica\InfopayCheckout\Enum\SessionStatusCode;
use Ux2Dev\Borica\InfopayCheckout\Http\HttpTransport;
use Ux2Dev\Borica\InfopayCheckout\Resource\SessionsResource;

function sessionsClient(array &$captured, array $responses): ClientInterface
{
    return new class($captured, $responses) implements ClientInterface {
        public function __construct(private array &$captured, private array $queue) {}
        public function sendRequest(RequestInterface $r): \Psr\Http\Message\ResponseInterface
        {
            $this->captured[] = $r;
            $next = array_shift($this->queue);
            if ($next === null) { throw new RuntimeException('no more responses queued'); }
            return $next;
        }
    };
}

beforeEach(function () {
    $this->config = new CheckoutConfig(
        baseUrl: 'https://uat-api-checkout.infopay.bg',
        authId: 'my-auth-id',
        authSecret: 'my-auth-secret',
        shopId: 'shop-1',
        privateKey: file_get_contents(__DIR__ . '/../../fixtures/test_private_key.pem'),
        certificate: file_get_contents(__DIR__ . '/../../fixtures/test_certificate.pem'),
    );
    $this->factory = new HttpFactory();
});

test('create POSTs authId/authSecret and returns Session', function () {
    $captured = [];
    $client = sessionsClient($captured, [
        new Psr7Response(200, [], json_encode([
            'sessionId' => 'sess-1',
            'sessionKey' => 'sess-key',
            'status' => 'Success',
        ])),
    ]);
    $transport = new HttpTransport($client, $this->factory, $this->factory);
    $resource = new SessionsResource($this->config, $transport);

    $session = $resource->create($this->config->authId, $this->config->authSecret);

    expect($session->sessionId)->toBe('sess-1');
    expect($session->status)->toBe(SessionCreateStatus::Success);

    expect($captured[0]->getMethod())->toBe('POST');
    expect((string) $captured[0]->getUri())->toBe('https://uat-api-checkout.infopay.bg/v1/api/sessions');
    $body = json_decode((string) $captured[0]->getBody(), true);
    expect($body)->toBe(['authId' => 'my-auth-id', 'authSecret' => 'my-auth-secret']);
});

test('close POSTs to /sessions/close with Basic auth header', function () {
    $captured = [];
    $client = sessionsClient($captured, [new Psr7Response(204, [], '')]);
    $transport = new HttpTransport($client, $this->factory, $this->factory);
    $resource = new SessionsResource($this->config, $transport);

    $session = new Session('id', 'key', SessionCreateStatus::Success);
    $resource->close($session);

    expect($captured[0]->getMethod())->toBe('POST');
    expect((string) $captured[0]->getUri())->toBe('https://uat-api-checkout.infopay.bg/v1/api/sessions/close');
    expect($captured[0]->getHeaderLine('Authorization'))->toBe('Basic ' . base64_encode('id:key'));
});

test('check returns SessionStatusCode', function () {
    $captured = [];
    $client = sessionsClient($captured, [
        new Psr7Response(200, [], json_encode(['sessionStatus' => 'Valid'])),
    ]);
    $transport = new HttpTransport($client, $this->factory, $this->factory);
    $resource = new SessionsResource($this->config, $transport);

    $session = new Session('id', 'key', SessionCreateStatus::Success);
    $status = $resource->check($session);

    expect($status)->toBe(SessionStatusCode::Valid);
    expect((string) $captured[0]->getUri())->toBe('https://uat-api-checkout.infopay.bg/v1/api/sessions/check');
});

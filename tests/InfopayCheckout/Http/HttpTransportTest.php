<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Ux2Dev\Borica\Exception\ApiException;
use Ux2Dev\Borica\Exception\AuthenticationException;
use Ux2Dev\Borica\Exception\InvalidResponseException;
use Ux2Dev\Borica\Exception\TransportException;
use Ux2Dev\Borica\InfopayCheckout\Http\HttpTransport;

function makeClient(callable $handler): ClientInterface
{
    return new class($handler) implements ClientInterface {
        public function __construct(private $handler) {}
        public function sendRequest(RequestInterface $request): \Psr\Http\Message\ResponseInterface
        {
            return ($this->handler)($request);
        }
    };
}

beforeEach(function () {
    $this->factory = new HttpFactory();
});

test('POST with JSON body returns parsed response for 2xx', function () {
    $captured = null;
    $client = makeClient(function (RequestInterface $req) use (&$captured) {
        $captured = $req;
        return new Response(200, ['Content-Type' => 'application/json'], json_encode(['ok' => true]));
    });

    $transport = new HttpTransport($client, $this->factory, $this->factory);

    $result = $transport->sendJson(
        method: 'POST',
        url: 'https://api.example.com/sessions',
        headers: ['Authorization' => 'Basic abc'],
        body: ['authId' => 'u', 'authSecret' => 's'],
    );

    expect($result)->toBe(['ok' => true]);
    expect($captured->getMethod())->toBe('POST');
    expect((string) $captured->getBody())->toBe('{"authId":"u","authSecret":"s"}');
    expect($captured->getHeaderLine('Authorization'))->toBe('Basic abc');
    expect($captured->getHeaderLine('Content-Type'))->toBe('application/json');
});

test('GET with no body omits Content-Type', function () {
    $captured = null;
    $client = makeClient(function (RequestInterface $req) use (&$captured) {
        $captured = $req;
        return new Response(200, [], json_encode(['status' => 'ok']));
    });

    $transport = new HttpTransport($client, $this->factory, $this->factory);

    $result = $transport->sendJson('GET', 'https://api.example.com/status/abc', []);

    expect($result)->toBe(['status' => 'ok']);
    expect($captured->getMethod())->toBe('GET');
    expect((string) $captured->getBody())->toBe('');
});

test('401 maps to AuthenticationException', function () {
    $client = makeClient(fn () => new Response(401, [], json_encode(['error' => 'unauthorized'])));
    $transport = new HttpTransport($client, $this->factory, $this->factory);

    expect(fn () => $transport->sendJson('GET', 'https://x', []))
        ->toThrow(AuthenticationException::class);
});

test('400 and other non-2xx map to ApiException with status + body', function () {
    $client = makeClient(fn () => new Response(409, [], json_encode(['error' => 'conflict'])));
    $transport = new HttpTransport($client, $this->factory, $this->factory);

    try {
        $transport->sendJson('POST', 'https://x', [], ['foo' => 'bar']);
        throw new \RuntimeException('expected throw');
    } catch (ApiException $e) {
        expect($e->getHttpStatus())->toBe(409);
        expect($e->getBody())->toBe(['error' => 'conflict']);
    }
});

test('PSR-18 ClientException maps to TransportException', function () {
    $client = makeClient(function () {
        throw new class extends \RuntimeException implements ClientExceptionInterface {};
    });
    $transport = new HttpTransport($client, $this->factory, $this->factory);

    expect(fn () => $transport->sendJson('GET', 'https://x', []))
        ->toThrow(TransportException::class);
});

test('non-JSON body on 200 throws InvalidResponseException', function () {
    $client = makeClient(fn () => new Response(200, [], '<html>not json</html>'));
    $transport = new HttpTransport($client, $this->factory, $this->factory);

    expect(fn () => $transport->sendJson('GET', 'https://x', []))
        ->toThrow(InvalidResponseException::class);
});

test('empty body on 200 throws InvalidResponseException', function () {
    $client = makeClient(fn () => new Response(200, [], ''));
    $transport = new HttpTransport($client, $this->factory, $this->factory);

    expect(fn () => $transport->sendJson('GET', 'https://x', []))
        ->toThrow(InvalidResponseException::class);
});

test('204 No Content returns empty array without parsing body', function () {
    $client = makeClient(fn () => new Response(204, [], ''));
    $transport = new HttpTransport($client, $this->factory, $this->factory);

    expect($transport->sendJson('POST', 'https://x', []))->toBe([]);
});

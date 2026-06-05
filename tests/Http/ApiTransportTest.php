<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface as PsrRequest;
use Psr\Http\Message\ResponseInterface;
use Ux2Dev\Borica\Contracts\ApiRequest;
use Ux2Dev\Borica\Contracts\BoricaResult;
use Ux2Dev\Borica\Http\ApiResponse;
use Ux2Dev\Borica\Http\ApiTransport;

final class FakeResult implements BoricaResult
{
    public function __construct(public readonly string $id) {}

    public static function fromArray(array $data): static
    {
        return new self((string) ($data['id'] ?? ''));
    }
}

final class FakeApiRequest implements ApiRequest
{
    public function method(): string { return 'POST'; }
    public function endpoint(): string { return '/things'; }
    public function responseClass(): string { return FakeResult::class; }
    public function toArray(): array { return ['name' => 'x']; }
}

function fakeClient(ResponseInterface $response): ClientInterface
{
    return new class($response) implements ClientInterface {
        public function __construct(private ResponseInterface $r) {}
        public function sendRequest(PsrRequest $request): ResponseInterface { return $this->r; }
    };
}

it('hydrates a single-object body into a one-item ApiResponse', function () {
    $factory = new HttpFactory();
    $client = fakeClient(new Response(200, [], json_encode(['id' => '42'])));
    $transport = new ApiTransport($client, $factory, $factory);

    $resp = $transport->send(new FakeApiRequest(), 'https://api.test');

    expect($resp)->toBeInstanceOf(ApiResponse::class);
    expect($resp->count)->toBe(1);
    expect($resp->first())->toBeInstanceOf(FakeResult::class);
    expect($resp->first()->id)->toBe('42');
});

it('does not treat a non-scalar domain "status" key as envelope metadata', function () {
    $factory = new HttpFactory();
    // Body where `status` is domain data (a nested object), not "OK"/"ERROR".
    $client = fakeClient(new Response(200, [], json_encode(['id' => '7', 'status' => ['code' => 'X']])));
    $transport = new ApiTransport($client, $factory, $factory);

    $resp = $transport->send(new FakeApiRequest(), 'https://api.test');

    expect($resp->status)->toBeNull();          // not cast from the array
    expect($resp->isOk())->toBeTrue();           // null status = ok
    expect($resp->first())->toBeInstanceOf(FakeResult::class);
    expect($resp->first()->id)->toBe('7');
});

it('maps a {result:[...]} body into a multi-item ApiResponse', function () {
    $factory = new HttpFactory();
    $body = json_encode(['result' => [['id' => '1'], ['id' => '2']], 'count' => 2]);
    $client = fakeClient(new Response(200, [], $body));
    $transport = new ApiTransport($client, $factory, $factory);

    $resp = $transport->send(new FakeApiRequest(), 'https://api.test');

    expect($resp->count)->toBe(2);
    expect($resp->all())->toHaveCount(2);
    expect($resp->first()->id)->toBe('1');
});

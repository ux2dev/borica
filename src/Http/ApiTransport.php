<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Http;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Ux2Dev\Borica\Contracts\ApiRequest;
use Ux2Dev\Borica\Exception\ApiException;
use Ux2Dev\Borica\Exception\AuthenticationException;
use Ux2Dev\Borica\Exception\InvalidResponseException;
use Ux2Dev\Borica\Exception\TransportException;

/**
 * Shared PSR-18 JSON transport for the Infopay Checkout + ERP APIs. Sends a
 * request, maps HTTP/transport failures to the library's exceptions, and
 * either returns the decoded array (sendJson) or a hydrated ApiResponse (send).
 */
final class ApiTransport
{
    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * Dispatch a typed ApiRequest and hydrate the response into an ApiResponse.
     *
     * @param  array<string, string> $headers per-call auth / signature headers
     */
    public function send(ApiRequest $request, string $baseUrl, array $headers = []): ApiResponse
    {
        $body = $request->method() === 'GET' ? null : $request->toArray();
        $decoded = $this->sendJson($request->method(), $baseUrl . $request->endpoint(), $headers, $body);

        return $this->wrap($decoded, $request->responseClass());
    }

    /**
     * @param  array<string, string>     $headers
     * @param  array<string, mixed>|null $body
     * @return array<string, mixed>      decoded JSON, or [] for 204
     */
    public function sendJson(string $method, string $url, array $headers, ?array $body = null): array
    {
        $request = $this->requestFactory->createRequest($method, $url);

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if ($body !== null) {
            $request = $request
                ->withHeader('Content-Type', 'application/json')
                ->withBody($this->streamFactory->createStream($this->encode($body)));
        }

        $request = $request->withHeader('Accept', 'application/json');

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new TransportException('HTTP transport error: ' . $e->getMessage(), previous: $e);
        }

        $status = $response->getStatusCode();
        $raw = (string) $response->getBody();

        if ($status === 204) {
            return [];
        }

        $isError = $status === 401 || $status < 200 || $status >= 300;

        $decoded = [];
        $decodeError = null;
        if ($raw !== '') {
            try {
                $maybe = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
                $decoded = is_array($maybe) ? $maybe : ['_raw' => $raw];
            } catch (\JsonException $e) {
                $decodeError = $e;
                $decoded = ['_raw' => $raw];
            }
        }

        if ($status === 401) {
            throw new AuthenticationException(message: 'Authentication failed (401)', httpStatus: $status, body: $decoded);
        }

        if ($isError) {
            throw new ApiException(message: "Infopay API returned HTTP {$status}", httpStatus: $status, body: $decoded);
        }

        if ($decodeError !== null) {
            throw new InvalidResponseException(
                "Malformed JSON response (HTTP {$status}): " . $decodeError->getMessage(),
                $decoded,
                0,
                $decodeError,
            );
        }

        return $decoded;
    }

    /**
     * Wrap an already-decoded JSON body into a typed ApiResponse. Used by GET
     * reads that build their own URL/query and by send().
     *
     * @param  array<string, mixed>      $decoded
     * @param  class-string<\Ux2Dev\Borica\Contracts\BoricaResult> $responseClass
     */
    public function wrap(array $decoded, string $responseClass): ApiResponse
    {
        $hasResultList = array_key_exists('result', $decoded) && is_array($decoded['result']);
        $rows = $hasResultList ? $decoded['result'] : [$decoded];

        $items = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                throw new InvalidResponseException('Each response result entry must be an object');
            }
            $items[] = $responseClass::fromArray($row);
        }

        // Infopay response bodies are not a uniform envelope: a top-level
        // `status`/`code` key may be domain data (e.g. a nested status object),
        // not envelope metadata. Only treat scalars as envelope fields.
        $status = (isset($decoded['status']) && is_scalar($decoded['status'])) ? (string) $decoded['status'] : null;
        $code = (isset($decoded['code']) && is_scalar($decoded['code'])) ? (int) $decoded['code'] : null;
        $totalCount = (isset($decoded['total_count']) && is_scalar($decoded['total_count'])) ? (int) $decoded['total_count'] : null;

        return new ApiResponse(
            status: $status,
            code: $code,
            count: $hasResultList ? (isset($decoded['count']) && is_scalar($decoded['count']) ? (int) $decoded['count'] : count($items)) : count($items),
            totalCount: $totalCount,
            result: $items,
            raw: $decoded,
        );
    }

    /** @param array<string, mixed> $body */
    private function encode(array $body): string
    {
        try {
            return json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
        } catch (\JsonException $e) {
            throw new InvalidResponseException('Failed to encode request body: ' . $e->getMessage(), [], 0, $e);
        }
    }
}

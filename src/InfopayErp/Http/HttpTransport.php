<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Http;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Ux2Dev\Borica\Exception\ApiException;
use Ux2Dev\Borica\Exception\AuthenticationException;
use Ux2Dev\Borica\Exception\InvalidResponseException;
use Ux2Dev\Borica\Exception\TransportException;

/**
 * Thin PSR-18 JSON transport for the Infopay ERP Integration API. Sends a
 * request, parses the JSON response, and maps HTTP / transport failures
 * to the library's exception hierarchy. ERP endpoints use PascalCase wire
 * property names; this transport is payload-agnostic and just handles
 * encoding, decoding and status mapping.
 */
final class HttpTransport
{
    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * @param  array<string, string>     $headers Extra headers (SessionId, SessionKey, etc.)
     * @param  array<string, mixed>|null $body    JSON-encodable body. Null for GET / no body.
     * @return array<string, mixed>               Decoded JSON response, or [] for 204.
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

        // For non-2xx responses we tolerate non-JSON bodies (e.g. an Azure
        // Application Gateway 504 page) — surface the HTTP status as the
        // primary signal and stuff the raw body verbatim into the exception.
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
            throw new AuthenticationException(
                message: 'Authentication failed (401)',
                httpStatus: $status,
                body: $decoded,
            );
        }

        if ($isError) {
            throw new ApiException(
                message: "ERP API returned HTTP {$status}",
                httpStatus: $status,
                body: $decoded,
            );
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

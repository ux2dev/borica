<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayCheckout\Http;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Ux2Dev\Borica\Exception\ApiException;
use Ux2Dev\Borica\Exception\AuthenticationException;
use Ux2Dev\Borica\Exception\InvalidResponseException;
use Ux2Dev\Borica\Exception\TransportException;

/**
 * Thin PSR-18 JSON transport for the Infopay Checkout API. Sends a request,
 * parses the JSON response, and maps HTTP / transport failures to the
 * library's exception hierarchy.
 */
final class HttpTransport
{
    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * @param  array<string, string>     $headers  Extra headers (Authorization, X-JWS-Signature, etc.)
     * @param  array<string, mixed>|null $body     JSON-encodable body. Null for GET / no body.
     * @return array<string, mixed>                Decoded JSON response, or [] for 204.
     */
    public function sendJson(string $method, string $url, array $headers, ?array $body = null): array
    {
        $request = $this->requestFactory->createRequest($method, $url);

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if ($body !== null) {
            $encoded = $this->encode($body);
            $request = $request
                ->withHeader('Content-Type', 'application/json')
                ->withBody($this->streamFactory->createStream($encoded));
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

        $decoded = $this->decode($raw, $status);

        if ($status === 401) {
            throw new AuthenticationException(
                message: 'Authentication failed (401)',
                httpStatus: $status,
                body: $decoded,
            );
        }

        if ($status < 200 || $status >= 300) {
            throw new ApiException(
                message: "Checkout API returned HTTP {$status}",
                httpStatus: $status,
                body: $decoded,
            );
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed> $body
     */
    private function encode(array $body): string
    {
        try {
            return json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
        } catch (\JsonException $e) {
            throw new InvalidResponseException('Failed to encode request body: ' . $e->getMessage(), [], 0, $e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(string $raw, int $status): array
    {
        if ($raw === '') {
            throw new InvalidResponseException("Empty response body (HTTP {$status})");
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new InvalidResponseException(
                "Malformed JSON response (HTTP {$status}): " . $e->getMessage(),
                [],
                0,
                $e,
            );
        }

        if (! is_array($decoded)) {
            throw new InvalidResponseException('Expected JSON object, got ' . gettype($decoded));
        }

        return $decoded;
    }
}

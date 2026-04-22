<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Tests\InfopayErp;

use GuzzleHttp\Psr7\Response as Psr7Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 * Test helpers shared across InfopayErp resource tests. Wraps a tiny PSR-18
 * client that captures every outgoing request and pops a queued response.
 */
final class FakeHttpClient implements ClientInterface
{
    /** @var array<int, RequestInterface> */
    public array $captured = [];

    /** @var array<int, ResponseInterface> */
    private array $queue;

    /** @param array<int, ResponseInterface> $queue */
    public function __construct(array $queue)
    {
        $this->queue = $queue;
    }

    public function sendRequest(RequestInterface $r): ResponseInterface
    {
        $this->captured[] = $r;
        $next = array_shift($this->queue);
        if ($next === null) {
            throw new RuntimeException('No more responses queued');
        }
        return $next;
    }

    /** @param array<string, mixed>|string $body */
    public static function json(int $status, array|string $body = []): Psr7Response
    {
        $payload = is_string($body) ? $body : json_encode($body, JSON_THROW_ON_ERROR);
        return new Psr7Response($status, ['Content-Type' => 'application/json'], $payload);
    }

    public static function noContent(): Psr7Response
    {
        return new Psr7Response(204, [], '');
    }
}

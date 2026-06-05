<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Http;

/**
 * Typed envelope over an Infopay HTTP response. For single-object endpoints
 * `result` holds exactly one hydrated DTO and `count` is 1.
 *
 * @template T
 */
final class ApiResponse
{
    /**
     * @param array<int, T>        $result
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public readonly ?string $status,
        public readonly ?int $code,
        public readonly ?int $count,
        public readonly ?int $totalCount,
        public readonly array $result,
        public readonly array $raw,
    ) {
    }

    /** @return T|null */
    public function first(): mixed
    {
        return $this->result[0] ?? null;
    }

    /** @return array<int, T> */
    public function all(): array
    {
        return $this->result;
    }

    public function isOk(): bool
    {
        return $this->status === null || strtoupper($this->status) === 'OK';
    }
}

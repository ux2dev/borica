<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Exception;

class ApiException extends BoricaException
{
    /** @var array<string, mixed> */
    private readonly array $body;

    /**
     * @param array<string, mixed> $body
     */
    public function __construct(
        string $message,
        private readonly int $httpStatus,
        array $body = [],
        ?\Throwable $previous = null,
    ) {
        $this->body = $body;
        parent::__construct($message, 0, $previous);
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    /** @return array<string, mixed> */
    public function getBody(): array
    {
        return $this->body;
    }
}

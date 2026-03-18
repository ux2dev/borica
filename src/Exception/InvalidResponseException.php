<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Exception;

class InvalidResponseException extends BoricaException
{
    public function __construct(
        string $message,
        private readonly array $responseData = [],
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getResponseData(): array
    {
        return $this->responseData;
    }
}

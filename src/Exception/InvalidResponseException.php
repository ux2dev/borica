<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Exception;

class InvalidResponseException extends BoricaException
{
    private const SENSITIVE_KEYS = ['CARD', 'APPROVAL', 'P_SIGN', 'RRN', 'INT_REF', 'CARDHOLDERINFO'];

    public function __construct(
        string $message,
        array $responseData = [],
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        foreach (self::SENSITIVE_KEYS as $key) {
            if (isset($responseData[$key])) {
                $responseData[$key] = '[REDACTED]';
            }
        }
        $this->responseData = $responseData;
        parent::__construct($message, $code, $previous);
    }

    /** @var array<string, mixed> */
    private readonly array $responseData;

    public function getResponseData(): array
    {
        return $this->responseData;
    }
}

<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

/**
 * Credentials for POST /api/session — uniqueId + accessToken are provided
 * to the merchant as part of the ERP registration process in InfoPay.
 */
final readonly class SessionCreateRequest
{
    public function __construct(
        public string $uniqueId,
        public string $accessToken,
    ) {}

    /** @return array<string, string> */
    public function toArray(): array
    {
        return [
            'uniqueId' => $this->uniqueId,
            'accessToken' => $this->accessToken,
        ];
    }
}

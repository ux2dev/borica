<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

use Ux2Dev\Borica\Contracts\ApiRequest;

/**
 * Credentials for POST /api/session — uniqueId + accessToken are provided
 * to the merchant as part of the ERP registration process in InfoPay.
 */
final readonly class SessionCreateRequest implements ApiRequest
{
    public function __construct(
        public string $uniqueId,
        public string $accessToken,
    ) {}

    public function method(): string
    {
        return 'POST';
    }

    public function endpoint(): string
    {
        return '/api/session';
    }

    public function responseClass(): string
    {
        return Session::class;
    }

    /** @return array<string, string> */
    public function toArray(): array
    {
        return [
            'uniqueId' => $this->uniqueId,
            'accessToken' => $this->accessToken,
        ];
    }
}

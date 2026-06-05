<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayCheckout\Dto;

use Ux2Dev\Borica\Contracts\ApiRequest;

final readonly class CreateSessionRequest implements ApiRequest
{
    public function __construct(
        public string $authId,
        public string $authSecret,
    ) {}

    public function method(): string
    {
        return 'POST';
    }

    public function endpoint(): string
    {
        return '/v1/api/sessions';
    }

    public function responseClass(): string
    {
        return Session::class;
    }

    /** @return array<string, string> */
    public function toArray(): array
    {
        return ['authId' => $this->authId, 'authSecret' => $this->authSecret];
    }
}

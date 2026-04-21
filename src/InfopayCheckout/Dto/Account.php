<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayCheckout\Dto;

final readonly class Account
{
    public function __construct(
        public string $iban,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['iban' => $this->iban];
    }
}

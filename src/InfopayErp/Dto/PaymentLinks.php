<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

final readonly class PaymentLinks
{
    public function __construct(
        public string $scaRedirect,
        public string $status,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            scaRedirect: (string) ($data['ScaRedirect'] ?? ''),
            status: (string) ($data['Status'] ?? ''),
        );
    }
}

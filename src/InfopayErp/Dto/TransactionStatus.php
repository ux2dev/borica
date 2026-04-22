<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

use Ux2Dev\Borica\InfopayErp\Enum\PaymentStatusCode;

final readonly class TransactionStatus
{
    public function __construct(
        public PaymentStatusCode $status,
        public bool $isFinal,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            status: PaymentStatusCode::from((string) ($data['Status'] ?? '')),
            isFinal: (bool) ($data['IsFinal'] ?? false),
        );
    }
}

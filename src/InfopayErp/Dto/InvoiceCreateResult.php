<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

final readonly class InvoiceCreateResult
{
    public function __construct(
        public string $invoiceId,
        public string $number,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            invoiceId: (string) ($data['invoiceId'] ?? ''),
            number: (string) ($data['number'] ?? ''),
        );
    }
}

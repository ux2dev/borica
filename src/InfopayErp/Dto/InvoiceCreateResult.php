<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

use Ux2Dev\Borica\Contracts\BoricaResult;

final readonly class InvoiceCreateResult implements BoricaResult
{
    public function __construct(
        public string $invoiceId,
        public string $number,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            invoiceId: (string) ($data['invoiceId'] ?? ''),
            number: (string) ($data['number'] ?? ''),
        );
    }
}

<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

final readonly class BankTransferAccount
{
    public function __construct(
        public string $bank,
        public string $iban,
        public string $currency,
    ) {}

    /** @return array<string, string> */
    public function toArray(): array
    {
        return [
            'bank' => $this->bank,
            'iban' => $this->iban,
            'currency' => $this->currency,
        ];
    }
}

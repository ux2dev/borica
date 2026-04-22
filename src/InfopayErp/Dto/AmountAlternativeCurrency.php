<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

use Ux2Dev\Borica\InfopayErp\Enum\Currency;

final readonly class AmountAlternativeCurrency
{
    public function __construct(
        public float $amount,
        public float $vatAmount,
        public float $amountVatIncluded,
        public Currency $currency,
        public ExchangeRate $exchangeRate,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'vatAmount' => $this->vatAmount,
            'amountVATIncluded' => $this->amountVatIncluded,
            'currency' => $this->currency->value,
            'exchangeRate' => $this->exchangeRate->toArray(),
        ];
    }
}

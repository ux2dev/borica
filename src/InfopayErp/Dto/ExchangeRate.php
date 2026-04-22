<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

use Ux2Dev\Borica\InfopayErp\Enum\Currency;

final readonly class ExchangeRate
{
    public function __construct(
        public float $rate,
        public Currency $baseCurrency,
        public Currency $quoteCurrency,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'rate' => $this->rate,
            'baseCurrency' => $this->baseCurrency->value,
            'quoteCurrency' => $this->quoteCurrency->value,
        ];
    }
}

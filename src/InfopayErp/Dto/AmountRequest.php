<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

use Ux2Dev\Borica\InfopayErp\Enum\Currency;

/**
 * Request-side amount — PascalCase wire keys (Amount, Currency), string
 * amount up to 11 digits + 2 decimal places per ^[0-9]{1,11}([.|,][0-9]{0,2}){0,1}$.
 */
final readonly class AmountRequest
{
    public function __construct(
        public string $amount,
        public Currency $currency,
    ) {}

    /** @return array<string, string> */
    public function toArray(): array
    {
        return [
            'Amount' => $this->amount,
            'Currency' => $this->currency->value,
        ];
    }
}

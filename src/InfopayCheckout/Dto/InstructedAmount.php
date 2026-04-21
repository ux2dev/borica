<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayCheckout\Dto;

use Ux2Dev\Borica\InfopayCheckout\Enum\InstructedAmountCurrency;

final readonly class InstructedAmount
{
    public function __construct(
        public float $amount,
        public InstructedAmountCurrency $currency,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency->value,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

final readonly class AmountPayableWithoutVat
{
    public function __construct(
        public float $amount,
        public ?AmountAlternativeCurrencyWithoutVat $amountAlternativeCurrency = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $out = ['amount' => $this->amount];
        if ($this->amountAlternativeCurrency !== null) {
            $out['amountAlternativeCurrency'] = $this->amountAlternativeCurrency->toArray();
        }
        return $out;
    }
}

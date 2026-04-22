<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

final readonly class AmountsByVatGroup
{
    public function __construct(
        public float $vatRate,
        public float $amount,
        public float $vatAmount,
        public float $amountVatIncluded,
        public ?DiscountWithVat $discount = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $out = [
            'vatRate' => $this->vatRate,
            'amount' => $this->amount,
            'vatAmount' => $this->vatAmount,
            'amountVATIncluded' => $this->amountVatIncluded,
        ];
        if ($this->discount !== null) {
            $out['discount'] = $this->discount->toArray();
        }
        return $out;
    }
}

<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

final readonly class ItemWithVat
{
    public function __construct(
        public string $name,
        public string $measureUnit,
        public float $quantity,
        public float $unitPrice,
        public VatRate $vatRate,
        public float $amount,
        public float $vatAmount,
        public float $amountVatIncluded,
        public ?string $description = null,
        public ?DiscountWithVat $discount = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $out = [
            'name' => $this->name,
            'measureUnit' => $this->measureUnit,
            'quantity' => $this->quantity,
            'unitPrice' => $this->unitPrice,
            'vatRate' => $this->vatRate->toArray(),
            'amount' => $this->amount,
            'vatAmount' => $this->vatAmount,
            'amountVATIncluded' => $this->amountVatIncluded,
        ];
        if ($this->description !== null) {
            $out['description'] = $this->description;
        }
        if ($this->discount !== null) {
            $out['discount'] = $this->discount->toArray();
        }
        return $out;
    }
}

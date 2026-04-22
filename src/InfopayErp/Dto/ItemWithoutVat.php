<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

final readonly class ItemWithoutVat
{
    public function __construct(
        public string $name,
        public string $measureUnit,
        public float $quantity,
        public float $unitPrice,
        public float $amount,
        public ?string $description = null,
        public ?DiscountWithoutVat $discount = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $out = [
            'name' => $this->name,
            'measureUnit' => $this->measureUnit,
            'quantity' => $this->quantity,
            'unitPrice' => $this->unitPrice,
            'amount' => $this->amount,
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

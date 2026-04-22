<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

final readonly class DiscountWithVat
{
    public function __construct(
        public float $amount,
        public float $vatAmount,
        public float $amountVatIncluded,
        public ?string $description = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $out = [
            'amount' => $this->amount,
            'vatAmount' => $this->vatAmount,
            'amountVATIncluded' => $this->amountVatIncluded,
        ];
        if ($this->description !== null) {
            $out['description'] = $this->description;
        }
        return $out;
    }
}

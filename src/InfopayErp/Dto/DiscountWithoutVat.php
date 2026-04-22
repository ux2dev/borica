<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

final readonly class DiscountWithoutVat
{
    public function __construct(
        public float $amount,
        public ?string $description = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $out = ['amount' => $this->amount];
        if ($this->description !== null) {
            $out['description'] = $this->description;
        }
        return $out;
    }
}

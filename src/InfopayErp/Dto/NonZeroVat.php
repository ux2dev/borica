<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

use Ux2Dev\Borica\InfopayErp\Enum\VatRateType;

final readonly class NonZeroVat extends VatRate
{
    public function __construct(
        public float $percentage,
    ) {}

    public function type(): VatRateType
    {
        return VatRateType::NonZeroVat;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'vatRateType' => $this->type()->value,
            'percentage' => $this->percentage,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArrayConcrete(array $data): self
    {
        return new self(percentage: (float) self::requireField($data, 'percentage'));
    }
}

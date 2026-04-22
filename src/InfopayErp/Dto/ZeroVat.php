<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

use Ux2Dev\Borica\InfopayErp\Enum\VatRateType;

final readonly class ZeroVat extends VatRate
{
    public function __construct(
        public string $reason,
    ) {}

    public function type(): VatRateType
    {
        return VatRateType::ZeroVat;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'vatRateType' => $this->type()->value,
            'reason' => $this->reason,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArrayConcrete(array $data): self
    {
        return new self(reason: (string) self::requireField($data, 'reason'));
    }
}

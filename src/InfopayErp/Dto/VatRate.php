<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

use InvalidArgumentException;
use Ux2Dev\Borica\InfopayErp\Enum\VatRateType;

/**
 * Base class for invoice VAT rate variants. Concrete subclasses set
 * `vatRateType` and add their own fields (reason for ZeroVat, percentage
 * for NonZeroVat). The discriminator goes over the wire as `vatRateType`.
 */
abstract readonly class VatRate
{
    abstract public function type(): VatRateType;

    /** @return array<string, mixed> */
    abstract public function toArray(): array;

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        $type = VatRateType::from((string) ($data['vatRateType'] ?? ''));
        return match ($type) {
            VatRateType::ZeroVat => ZeroVat::fromArrayConcrete($data),
            VatRateType::NonZeroVat => NonZeroVat::fromArrayConcrete($data),
        };
    }

    /** @param array<string, mixed> $data */
    protected static function requireField(array $data, string $field): mixed
    {
        if (! array_key_exists($field, $data)) {
            throw new InvalidArgumentException("Missing required field: {$field}");
        }
        return $data[$field];
    }
}

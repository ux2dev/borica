<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

/**
 * Response-side amount. The spec sends amount as a string (to preserve
 * arbitrary precision up to 14 digits + 3 decimal places) with lowercase
 * property names. Keep the string representation as-is; do NOT coerce to
 * float, or you'll lose precision on large BGN sums.
 */
final readonly class AmountType
{
    public function __construct(
        public ?string $amount = null,
        public ?string $currency = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            amount: isset($data['amount']) ? (string) $data['amount'] : null,
            currency: isset($data['currency']) ? (string) $data['currency'] : null,
        );
    }
}

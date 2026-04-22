<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

use DateTimeImmutable;

final readonly class TransactionAccountReference
{
    public function __construct(
        public string $iban,
        public ?DateTimeImmutable $lastUpdateDateTime = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            iban: (string) ($data['IBAN'] ?? ''),
            lastUpdateDateTime: isset($data['LastUpdateDateTime'])
                ? new DateTimeImmutable((string) $data['LastUpdateDateTime'])
                : null,
        );
    }
}

<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

use DateTimeImmutable;

final readonly class AccountInfo
{
    public function __construct(
        public ?string $iban = null,
        public ?DateTimeImmutable $lastUpdateDateTime = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            iban: isset($data['IBAN']) ? (string) $data['IBAN'] : null,
            lastUpdateDateTime: isset($data['LastUpdateDateTime'])
                ? new DateTimeImmutable((string) $data['LastUpdateDateTime'])
                : null,
        );
    }
}

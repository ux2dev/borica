<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

final readonly class AccountReference
{
    public function __construct(
        public string $iban,
    ) {}

    /** @return array<string, string> */
    public function toArray(): array
    {
        return ['IBAN' => $this->iban];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(iban: (string) ($data['IBAN'] ?? ''));
    }
}

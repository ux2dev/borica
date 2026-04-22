<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

final readonly class LinksAccountDetails
{
    public function __construct(
        public ?HrefType $transactions = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            transactions: isset($data['transactions'])
                ? HrefType::fromArray((array) $data['transactions'])
                : null,
        );
    }
}

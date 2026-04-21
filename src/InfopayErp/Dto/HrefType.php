<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

final readonly class HrefType
{
    public function __construct(
        public ?string $href = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(href: isset($data['href']) ? (string) $data['href'] : null);
    }
}

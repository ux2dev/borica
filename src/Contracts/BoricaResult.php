<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Contracts;

interface BoricaResult
{
    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static;
}

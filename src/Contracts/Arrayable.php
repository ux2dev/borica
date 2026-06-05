<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Contracts;

interface Arrayable
{
    /** @return array<string, mixed> */
    public function toArray(): array;
}

<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Ux2Dev\Borica\Response\Response;

class BoricaPreAuthSucceeded
{
    use Dispatchable;

    public function __construct(
        public readonly Response $response,
        public readonly ?string $merchantName,
    ) {}
}

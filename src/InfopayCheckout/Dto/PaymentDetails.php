<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayCheckout\Dto;

use Ux2Dev\Borica\InfopayCheckout\Enum\PaymentType;

abstract readonly class PaymentDetails
{
    abstract public function type(): PaymentType;

    /** @return array<string, mixed> */
    abstract public function toArray(): array;
}

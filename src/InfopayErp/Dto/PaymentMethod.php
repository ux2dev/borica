<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

use Ux2Dev\Borica\InfopayErp\Enum\PaymentMethodType;

/**
 * Abstract payment method for invoice PaymentDetails — concrete variants
 * are BankTransfer, Cash, Other, Card. Discriminator key is `paymentType`.
 */
abstract readonly class PaymentMethod
{
    abstract public function type(): PaymentMethodType;

    /** @return array<string, mixed> */
    abstract public function toArray(): array;
}

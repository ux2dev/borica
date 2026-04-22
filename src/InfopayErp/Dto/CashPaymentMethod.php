<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

use Ux2Dev\Borica\InfopayErp\Enum\PaymentMethodType;

final readonly class CashPaymentMethod extends PaymentMethod
{
    public function type(): PaymentMethodType
    {
        return PaymentMethodType::Cash;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['paymentType' => $this->type()->value];
    }
}

<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Enum;

enum PaymentMethodType: string
{
    case BankTransfer = 'bankTransfer';
    case Card = 'card';
    case Cash = 'cash';
    case Other = 'other';
}

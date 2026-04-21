<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayCheckout\Enum;

enum SepaServiceLevel: string
{
    case Sepa = 'SEPA';
    case Inst = 'INST';
}

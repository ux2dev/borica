<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Enum;

enum Currency: string
{
    case Bgn = 'BGN';
    case Eur = 'EUR';
    case Usd = 'USD';
    case Gbp = 'GBP';
    case Chf = 'CHF';
}

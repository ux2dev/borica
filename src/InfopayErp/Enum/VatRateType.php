<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Enum;

enum VatRateType: string
{
    case ZeroVat = 'zeroVAT';
    case NonZeroVat = 'nonZeroVAT';
}

<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Enum;

enum TaxPayerType: string
{
    case Egn = 'EGN';
    case Eik = 'EIK';
    case Pnf = 'PNF';
}

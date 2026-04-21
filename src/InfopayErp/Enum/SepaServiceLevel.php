<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Enum;

enum SepaServiceLevel: string
{
    case Sepa = 'SEPA';
    case Inst = 'INST';
}

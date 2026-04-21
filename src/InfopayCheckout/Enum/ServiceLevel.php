<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayCheckout\Enum;

enum ServiceLevel: string
{
    case Next = 'NEXT';
    case Urgp = 'URGP';
    case Blnk = 'BLNK';
}

<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayCheckout\Enum;

enum SessionStatusCode: string
{
    case NoSession = 'NoSession';
    case Expired = 'Expired';
    case Valid = 'Valid';
    case Invalid = 'Invalid';
}

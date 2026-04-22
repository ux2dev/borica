<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Enum;

enum SessionState: string
{
    case NoSession = 'NoSession';
    case Expired = 'Expired';
    case Valid = 'Valid';
    case Invalid = 'Invalid';
}

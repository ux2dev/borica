<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Enum;

enum PaymentState: string
{
    case New = 'New';
    case Sent = 'Sent';
    case Locked = 'Locked';
    case Closed = 'Closed';
}

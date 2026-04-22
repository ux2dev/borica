<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Enum;

enum SessionCreateStatus: string
{
    case Success = 'Success';
    case InvaliCredentials = 'InvaliCredentials';
    case Blocked = 'Blocked';
}

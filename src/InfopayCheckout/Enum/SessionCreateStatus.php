<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayCheckout\Enum;

enum SessionCreateStatus: string
{
    case Success = 'Success';
    case InvalidCredentials = 'InvalidCredentials';
    case Blocked = 'Blocked';
}

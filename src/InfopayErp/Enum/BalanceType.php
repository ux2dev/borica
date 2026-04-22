<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Enum;

enum BalanceType: string
{
    case ActualBalance = 'ActualBalance';
    case AvailableBalance = 'AvailableBalance';
    case BeginDay = 'BeginDay';
}

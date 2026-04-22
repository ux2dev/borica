<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Enum;

enum TransactionType: string
{
    case Debit = 'Debit';
    case Credit = 'Credit';
}

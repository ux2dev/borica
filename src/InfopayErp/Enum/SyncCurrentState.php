<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Enum;

enum SyncCurrentState: string
{
    case None = 'None';
    case Processing = 'Processing';
    case Success = 'Success';
    case Failed = 'Failed';
}

<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Enum;

enum SigningSchema: string
{
    case MacGeneral = 'MAC_GENERAL';
    case MacExtended = 'MAC_EXTENDED';
    case MacAdvanced = 'MAC_ADVANCED';
}

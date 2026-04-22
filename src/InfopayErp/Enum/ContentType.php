<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Enum;

enum ContentType: string
{
    case ContentWithVat = 'contentWithVAT';
    case ContentWithoutVat = 'contentWithoutVAT';
}

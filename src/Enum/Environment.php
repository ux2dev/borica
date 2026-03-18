<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Enum;

enum Environment: string
{
    case Development = 'https://3dsgate-dev.borica.bg/cgi-bin/cgi_link';
    case Production = 'https://3dsgate.borica.bg/cgi-bin/cgi_link';
}

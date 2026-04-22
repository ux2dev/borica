<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

use Ux2Dev\Borica\InfopayErp\Enum\ContentType;

/**
 * Abstract invoice content — concrete subclasses (ContentWithVat /
 * ContentWithoutVat) carry the actual items and payable amounts. The
 * discriminator is `contentType` per the spec's oneOf.
 */
abstract readonly class Content
{
    abstract public function type(): ContentType;

    /** @return array<string, mixed> */
    abstract public function toArray(): array;
}

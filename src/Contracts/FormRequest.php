<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Contracts;

/**
 * A CGI form request: signed and rendered as HTML form fields for a browser
 * redirect to the BORICA gateway. Not an HTTP round-trip — no response DTO.
 */
interface FormRequest extends Arrayable
{
    /** Ordered field names included in the MAC / signature. */
    public function signingFields(): array;
}

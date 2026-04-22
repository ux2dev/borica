<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

/**
 * HATEOAS pagination link block returned on transaction collections. The
 * `next` href (when present) points to the next page — paginators should
 * follow it verbatim until it's null.
 */
final readonly class LinksTransactionsDetails
{
    public function __construct(
        public ?HrefType $next = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            next: isset($data['Next']) ? HrefType::fromArray((array) $data['Next']) : null,
        );
    }
}

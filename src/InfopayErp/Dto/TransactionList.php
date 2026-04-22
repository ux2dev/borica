<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

/**
 * The `Transactions` block inside a transactions GET response — booked
 * transactions plus the pagination link block.
 */
final readonly class TransactionList
{
    /** @param array<int, Transaction> $booked */
    public function __construct(
        public array $booked = [],
        public ?LinksTransactionsDetails $links = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $booked = [];
        foreach ((array) ($data['Booked'] ?? []) as $t) {
            $booked[] = Transaction::fromArray((array) $t);
        }
        return new self(
            booked: $booked,
            links: isset($data['Links']) ? LinksTransactionsDetails::fromArray((array) $data['Links']) : null,
        );
    }
}

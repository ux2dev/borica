<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

/**
 * One page of a transactions query — wraps the account reference, balances
 * at query time, and the paginated list of booked transactions.
 */
final readonly class TransactionsPage
{
    /** @param array<int, Balance> $balances */
    public function __construct(
        public ?TransactionAccountReference $account = null,
        public array $balances = [],
        public ?TransactionList $transactions = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $balances = [];
        foreach ((array) ($data['Balances'] ?? []) as $b) {
            $balances[] = Balance::fromArray((array) $b);
        }

        return new self(
            account: isset($data['Account']) ? TransactionAccountReference::fromArray((array) $data['Account']) : null,
            balances: $balances,
            transactions: isset($data['Transactions']) ? TransactionList::fromArray((array) $data['Transactions']) : null,
        );
    }

    public function nextUrl(): ?string
    {
        return $this->transactions?->links?->next?->href;
    }
}

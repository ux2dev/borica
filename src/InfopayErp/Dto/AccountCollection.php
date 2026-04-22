<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

final readonly class AccountCollection
{
    /** @param array<int, Account> $accounts */
    public function __construct(
        public array $accounts = [],
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $accounts = [];
        foreach ((array) ($data['Accounts'] ?? []) as $a) {
            $accounts[] = Account::fromArray((array) $a);
        }
        return new self(accounts: $accounts);
    }
}

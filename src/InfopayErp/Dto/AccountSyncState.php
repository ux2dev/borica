<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

/**
 * Combined sync state for a single account — wraps the balance sync and
 * transaction sync sub-states. Note that the wire key for the transaction
 * sync is the spec-level typo `TransactioneCurrentState` (extra `e`); the
 * PHP property name uses the correct spelling.
 */
final readonly class AccountSyncState
{
    public function __construct(
        public string $accountId,
        public string $iban,
        public ?BalanceSyncState $balanceCurrentState = null,
        public ?TransactionSyncState $transactionCurrentState = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            accountId: (string) ($data['AccountId'] ?? ''),
            iban: (string) ($data['IBAN'] ?? ''),
            balanceCurrentState: isset($data['BalanceCurrentState'])
                ? BalanceSyncState::fromArray((array) $data['BalanceCurrentState'])
                : null,
            transactionCurrentState: isset($data['TransactioneCurrentState'])
                ? TransactionSyncState::fromArray((array) $data['TransactioneCurrentState'])
                : null,
        );
    }
}

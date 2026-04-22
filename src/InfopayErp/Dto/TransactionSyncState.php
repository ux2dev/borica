<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

use DateTimeImmutable;
use Ux2Dev\Borica\InfopayErp\Enum\SyncCurrentState;

final readonly class TransactionSyncState
{
    public function __construct(
        public SyncCurrentState $state,
        public ?DateTimeImmutable $transactionLastAttemptSyncDate = null,
        public ?DateTimeImmutable $transactionLastSuccessSyncDate = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            state: SyncCurrentState::from((string) ($data['State'] ?? '')),
            transactionLastAttemptSyncDate: isset($data['TransactionLastAttemptSyncDate'])
                ? new DateTimeImmutable((string) $data['TransactionLastAttemptSyncDate'])
                : null,
            transactionLastSuccessSyncDate: isset($data['TransactionLastSuccessSyncDate'])
                ? new DateTimeImmutable((string) $data['TransactionLastSuccessSyncDate'])
                : null,
        );
    }
}

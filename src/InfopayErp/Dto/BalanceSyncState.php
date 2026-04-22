<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

use DateTimeImmutable;
use Ux2Dev\Borica\InfopayErp\Enum\SyncCurrentState;

final readonly class BalanceSyncState
{
    public function __construct(
        public SyncCurrentState $state,
        public ?DateTimeImmutable $balanceLastSuccessSyncDate = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            state: SyncCurrentState::from((string) ($data['State'] ?? '')),
            balanceLastSuccessSyncDate: isset($data['BalanceLastSuccessSyncDate'])
                ? new DateTimeImmutable((string) $data['BalanceLastSuccessSyncDate'])
                : null,
        );
    }
}

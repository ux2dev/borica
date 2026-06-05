<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

use Ux2Dev\Borica\Contracts\BoricaResult;

final readonly class AccountSyncStateCollection implements BoricaResult
{
    /** @param array<int, AccountSyncState> $states */
    public function __construct(
        public array $states = [],
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        $states = [];
        foreach ((array) ($data['States'] ?? []) as $s) {
            $states[] = AccountSyncState::fromArray((array) $s);
        }
        return new self(states: $states);
    }

    public function allSucceeded(): bool
    {
        foreach ($this->states as $state) {
            $b = $state->balanceCurrentState?->state;
            $t = $state->transactionCurrentState?->state;
            if ($b !== \Ux2Dev\Borica\InfopayErp\Enum\SyncCurrentState::Success) {
                return false;
            }
            if ($t !== \Ux2Dev\Borica\InfopayErp\Enum\SyncCurrentState::Success) {
                return false;
            }
        }
        return $this->states !== [];
    }

    public function anyProcessing(): bool
    {
        foreach ($this->states as $state) {
            if ($state->balanceCurrentState?->state === \Ux2Dev\Borica\InfopayErp\Enum\SyncCurrentState::Processing) {
                return true;
            }
            if ($state->transactionCurrentState?->state === \Ux2Dev\Borica\InfopayErp\Enum\SyncCurrentState::Processing) {
                return true;
            }
        }
        return false;
    }
}

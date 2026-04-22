<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

/**
 * Request for POST /api/synchronizations/balancesAndTransactions/refresh.
 * When AccountIds is empty the server refreshes every account linked to
 * the ERP registration.
 */
final readonly class SyncRefreshRequest
{
    /** @param array<int, string> $accountIds */
    public function __construct(
        public array $accountIds = [],
    ) {}

    /** @return array<string, array<int, string>> */
    public function toArray(): array
    {
        return ['AccountIds' => array_values($this->accountIds)];
    }
}

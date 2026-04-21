<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

use DateTimeImmutable;

/**
 * Response for GET /api/accounts/{id}/transactionsMissingDates — reports
 * the dates in [dateFrom, dateTo] where no transactions have been synced.
 */
final readonly class MissingTransactionDates
{
    /** @param array<int, DateTimeImmutable> $notSyncedTransactionsDates */
    public function __construct(
        public bool $hasDatesNotSynced,
        public array $notSyncedTransactionsDates = [],
        public ?AccountInfo $account = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $dates = [];
        foreach ((array) ($data['NotSyncedTransactionsDates'] ?? []) as $d) {
            $dates[] = new DateTimeImmutable((string) $d);
        }

        return new self(
            hasDatesNotSynced: (bool) ($data['HasDatesNotSynced'] ?? false),
            notSyncedTransactionsDates: $dates,
            account: isset($data['Account']) ? AccountInfo::fromArray((array) $data['Account']) : null,
        );
    }
}

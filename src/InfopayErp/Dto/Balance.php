<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

use DateTimeImmutable;
use Ux2Dev\Borica\InfopayErp\Enum\BalanceType;

final readonly class Balance
{
    public function __construct(
        public AmountType $balanceAmount,
        public BalanceType $balanceType,
        public ?DateTimeImmutable $referenceDate = null,
        public ?DateTimeImmutable $balanceLastSuccessSyncDate = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            balanceAmount: AmountType::fromArray((array) ($data['BalanceAmount'] ?? [])),
            balanceType: BalanceType::from((string) ($data['BalanceType'] ?? '')),
            referenceDate: isset($data['ReferenceDate'])
                ? new DateTimeImmutable((string) $data['ReferenceDate'])
                : null,
            balanceLastSuccessSyncDate: isset($data['BalanceLastSuccessSyncDate'])
                ? new DateTimeImmutable((string) $data['BalanceLastSuccessSyncDate'])
                : null,
        );
    }
}

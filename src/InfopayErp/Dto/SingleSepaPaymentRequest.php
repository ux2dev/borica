<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

/**
 * POST /api/payments/sepa-credit-transfers — debtor account + one SEPA
 * payment. Wire preserves the spec typo `DebitorAccount`.
 */
final readonly class SingleSepaPaymentRequest
{
    public function __construct(
        public AccountReference $debtorAccount,
        public SepaPayment $payment,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'DebitorAccount' => $this->debtorAccount->toArray(),
            'Payment' => $this->payment->toArray(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

use Ux2Dev\Borica\Contracts\ApiRequest;

/**
 * POST /api/payments/sepa-credit-transfers — debtor account + one SEPA
 * payment. Wire preserves the spec typo `DebitorAccount`.
 */
final readonly class SingleSepaPaymentRequest implements ApiRequest
{
    public function __construct(
        public AccountReference $debtorAccount,
        public SepaPayment $payment,
    ) {}

    public function method(): string
    {
        return 'POST';
    }

    public function endpoint(): string
    {
        return '/api/payments/sepa-credit-transfers';
    }

    public function responseClass(): string
    {
        return PaymentResult::class;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'DebitorAccount' => $this->debtorAccount->toArray(),
            'Payment' => $this->payment->toArray(),
        ];
    }
}

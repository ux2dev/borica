<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

use InvalidArgumentException;
use Ux2Dev\Borica\Contracts\ApiRequest;

/**
 * POST /api/bulk-payments/sepa-credit-transfers — debtor account + 2..250
 * SEPA payments. Wire preserves the spec typo `DebitorAccount`.
 */
final readonly class BulkSepaPaymentRequest implements ApiRequest
{
    private const int MIN_PAYMENTS = 2;

    private const int MAX_PAYMENTS = 250;

    /** @param array<int, SepaPayment> $payments */
    public function __construct(
        public AccountReference $debtorAccount,
        public array $payments,
    ) {
        $count = count($payments);
        if ($count < self::MIN_PAYMENTS || $count > self::MAX_PAYMENTS) {
            throw new InvalidArgumentException(sprintf(
                'Bulk SEPA payment requires %d..%d payments, got %d',
                self::MIN_PAYMENTS,
                self::MAX_PAYMENTS,
                $count,
            ));
        }
    }

    public function method(): string
    {
        return 'POST';
    }

    public function endpoint(): string
    {
        return '/api/bulk-payments/sepa-credit-transfers';
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
            'Payments' => array_map(fn (SepaPayment $p) => $p->toArray(), $this->payments),
        ];
    }
}
